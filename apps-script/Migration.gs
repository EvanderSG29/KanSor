var LEGACY_MIGRATION_SHEET_KEYS = [
  "buyers",
  "savings",
  "suppliers",
  "transactions",
  "daily_finance",
  "change_entries",
  "supplier_payouts",
];

function getLegacyMigrationSheetKeys_(includeUsers) {
  var sheetKeys = LEGACY_MIGRATION_SHEET_KEYS.slice();

  if (includeUsers) {
    sheetKeys.unshift("users");
  }

  return sheetKeys;
}

function openSpreadsheetByIdForMigration_(spreadsheetId, label) {
  if (!spreadsheetId) {
    throw new Error(label + " wajib diisi.");
  }

  try {
    return SpreadsheetApp.openById(String(spreadsheetId).trim());
  } catch (error) {
    throw new Error(label + " tidak bisa dibuka. " + error.message);
  }
}

function getSheetHeadersFromSpreadsheet_(spreadsheet, schemaKey) {
  var schema = CONFIG.SHEETS[schemaKey];
  var sheet = spreadsheet.getSheetByName(schema.name);

  if (!sheet) {
    return [];
  }

  var lastColumn = Math.max(sheet.getLastColumn(), schema.headers.length);
  if (lastColumn < 1) {
    return [];
  }

  return sheet.getRange(1, 1, 1, lastColumn).getValues()[0]
    .map(function (value) {
      return String(value || "").trim();
    })
    .filter(function (header) {
      return Boolean(header);
    });
}

function getSheetRecordsFromSpreadsheet_(spreadsheet, schemaKey) {
  var schema = CONFIG.SHEETS[schemaKey];
  var sheet = spreadsheet.getSheetByName(schema.name);

  if (!sheet) {
    return [];
  }

  var values = sheet.getDataRange().getValues();
  if (values.length < 2) {
    return [];
  }

  var headers = values[0].map(function (value) {
    return String(value || "").trim();
  });

  return values.slice(1).map(function (row, index) {
    var record = { _rowNumber: index + 2 };

    headers.forEach(function (header, columnIndex) {
      if (!header) {
        return;
      }

      record[header] = normalizeCellValue_(row[columnIndex]);
    });

    return record;
  });
}

function buildLegacyMigrationSheetPreview_(sourceSpreadsheet, targetSpreadsheet, schemaKey) {
  var schema = CONFIG.SHEETS[schemaKey];
  var sourceHeaders = getSheetHeadersFromSpreadsheet_(sourceSpreadsheet, schemaKey);
  var targetHeaders = getSheetHeadersFromSpreadsheet_(targetSpreadsheet, schemaKey);
  var sourceRecords = getSheetRecordsFromSpreadsheet_(sourceSpreadsheet, schemaKey);
  var targetRecords = getSheetRecordsFromSpreadsheet_(targetSpreadsheet, schemaKey);
  var missingSourceHeaders = schema.headers.filter(function (header) {
    return sourceHeaders.indexOf(header) === -1;
  });
  var missingTargetHeaders = schema.headers.filter(function (header) {
    return targetHeaders.indexOf(header) === -1;
  });

  return {
    sheetKey: schemaKey,
    sheetName: schema.name,
    sourceRowCount: sourceRecords.length,
    targetRowCount: targetRecords.length,
    compatible: missingSourceHeaders.length === 0 && missingTargetHeaders.length === 0,
    missingSourceHeaders: missingSourceHeaders,
    missingTargetHeaders: missingTargetHeaders,
    extraSourceHeaders: sourceHeaders.filter(function (header) {
      return schema.headers.indexOf(header) === -1;
    }),
    extraTargetHeaders: targetHeaders.filter(function (header) {
      return schema.headers.indexOf(header) === -1;
    }),
    mode: schemaKey === "users" ? "merge_users_without_credentials" : "overwrite_sheet",
  };
}

function createSpreadsheetBackup_(spreadsheet, label) {
  var timestamp = Utilities.formatDate(new Date(), CONFIG.DEFAULT_TIMEZONE, "yyyyMMdd-HHmmss");

  try {
    var file = DriveApp.getFileById(spreadsheet.getId());
    var copy = file.makeCopy(label + " - " + spreadsheet.getName() + " - " + timestamp);

    return {
      ok: true,
      id: copy.getId(),
      name: copy.getName(),
      url: copy.getUrl(),
    };
  } catch (error) {
    return {
      ok: false,
      error: error.message,
    };
  }
}

function sanitizeMigratedUserRecord_(record, fallbackTimestamp) {
  var createdAt = String(record.created_at || fallbackTimestamp);
  var updatedAt = String(record.updated_at || createdAt);

  return {
    id: record.id || generateId_("USR"),
    full_name: String(record.full_name || "").trim(),
    nickname: String(record.nickname || "").trim(),
    email: String(record.email || "").trim(),
    role: record.role === "admin" ? "admin" : "petugas",
    status: record.status === "nonaktif" ? "nonaktif" : "aktif",
    class_group: String(record.class_group || "").trim(),
    pin_hash: "",
    notes: String(record.notes || "").trim(),
    created_at: createdAt,
    updated_at: updatedAt,
    password_hash: "",
    auth_updated_at: "",
  };
}

function mergeLegacyUsersIntoTarget_(sourceSpreadsheet) {
  var fallbackTimestamp = nowIso_();
  var sourceUsers = getSheetRecordsFromSpreadsheet_(sourceSpreadsheet, "users")
    .map(function (record) {
      return sanitizeMigratedUserRecord_(record, fallbackTimestamp);
    });
  var targetUsers = getSheetRecords_("users");
  var seenKeys = {};
  var mergedUsers = [];

  sourceUsers.forEach(function (record) {
    var key = normalizeText_(record.email) || String(record.id);
    if (!key || seenKeys[key]) {
      return;
    }

    seenKeys[key] = true;
    mergedUsers.push(record);
  });

  targetUsers.forEach(function (record) {
    var key = normalizeText_(record.email) || String(record.id);
    if (!key || seenKeys[key]) {
      return;
    }

    seenKeys[key] = true;
    mergedUsers.push(withoutMeta_(record));
  });

  return mergedUsers;
}

function clearLegacyUserReferences_(schemaKey, record) {
  if (schemaKey === "transactions") {
    record.input_by_user_id = "";
  }

  if (schemaKey === "savings") {
    record.recorded_by_user_id = "";
  }

  if (schemaKey === "daily_finance") {
    record.created_by_user_id = "";
  }

  if (schemaKey === "change_entries") {
    record.settled_by_user_id = "";
    record.created_by_user_id = "";
  }

  if (schemaKey === "supplier_payouts") {
    record.paid_by_user_id = "";
  }

  return record;
}

function buildMigratedSheetRecords_(sourceSpreadsheet, schemaKey, includeUsers) {
  if (schemaKey === "users") {
    return mergeLegacyUsersIntoTarget_(sourceSpreadsheet);
  }

  return getSheetRecordsFromSpreadsheet_(sourceSpreadsheet, schemaKey).map(function (record) {
    var migratedRecord = withoutMeta_(record);

    if (!includeUsers) {
      migratedRecord = clearLegacyUserReferences_(schemaKey, migratedRecord);
    }

    return migratedRecord;
  });
}

function overwriteSheetRecords_(spreadsheet, schemaKey, records) {
  var schema = CONFIG.SHEETS[schemaKey];
  var sheet = ensureSheetSchema_(spreadsheet, schema);
  var columnCount = Math.max(sheet.getMaxColumns(), schema.headers.length);

  if (sheet.getMaxRows() > 1) {
    sheet.getRange(2, 1, sheet.getMaxRows() - 1, columnCount).clearContent();
  }

  if (!records.length) {
    return;
  }

  var rows = records.map(function (record) {
    return schema.headers.map(function (header) {
      return record[header] === undefined ? "" : record[header];
    });
  });

  var requiredRows = rows.length + 1;
  if (sheet.getMaxRows() < requiredRows) {
    sheet.insertRowsAfter(sheet.getMaxRows(), requiredRows - sheet.getMaxRows());
  }

  sheet.getRange(2, 1, rows.length, schema.headers.length).setValues(rows);
}

function buildLegacyMigrationResult_(sourceSpreadsheet, targetSpreadsheet, includeUsers, dryRun) {
  var sheetPreviews = getLegacyMigrationSheetKeys_(includeUsers).map(function (schemaKey) {
    return buildLegacyMigrationSheetPreview_(sourceSpreadsheet, targetSpreadsheet, schemaKey);
  });

  return {
    sourceSpreadsheet: {
      id: sourceSpreadsheet.getId(),
      name: sourceSpreadsheet.getName(),
      url: sourceSpreadsheet.getUrl(),
    },
    targetSpreadsheet: {
      id: targetSpreadsheet.getId(),
      name: targetSpreadsheet.getName(),
      url: targetSpreadsheet.getUrl(),
    },
    dryRun: Boolean(dryRun),
    includeUsers: Boolean(includeUsers),
    warnings: includeUsers
      ? []
      : ["Referensi user lama akan diputus dari data histori, tetapi snapshot nama tetap dipertahankan."],
    sheets: sheetPreviews,
    totalSourceRows: sheetPreviews.reduce(function (sum, item) {
      return sum + item.sourceRowCount;
    }, 0),
    totalTargetRows: sheetPreviews.reduce(function (sum, item) {
      return sum + item.targetRowCount;
    }, 0),
  };
}

function executeLegacySpreadsheetMigration_(sourceSpreadsheet, targetSpreadsheet, includeUsers, allowWithoutBackups) {
  var result = buildLegacyMigrationResult_(sourceSpreadsheet, targetSpreadsheet, includeUsers, false);
  var incompatibleSheets = result.sheets.filter(function (sheet) {
    return !sheet.compatible;
  });

  if (incompatibleSheets.length) {
    throw new Error(
      "Migrasi dibatalkan. Header sheet tidak kompatibel: " + incompatibleSheets.map(function (sheet) {
        return sheet.sheetName;
      }).join(", ") + ".",
    );
  }

  var sourceBackup = createSpreadsheetBackup_(sourceSpreadsheet, "Backup sumber migrasi legacy POS Kantin");
  var targetBackup = createSpreadsheetBackup_(targetSpreadsheet, "Backup target sebelum migrasi POS Kantin");
  var backupFailures = [sourceBackup, targetBackup].filter(function (backup) {
    return !backup.ok;
  });

  if (backupFailures.length && !allowWithoutBackups) {
    throw new Error("Backup spreadsheet gagal. Jalankan ulang setelah izin Drive diperbaiki atau gunakan allowWithoutBackups.");
  }

  result.backups = {
    source: sourceBackup,
    target: targetBackup,
  };
  result.executed = true;
  result.migratedAt = nowIso_();

  getLegacyMigrationSheetKeys_(includeUsers).forEach(function (schemaKey) {
    overwriteSheetRecords_(
      targetSpreadsheet,
      schemaKey,
      buildMigratedSheetRecords_(sourceSpreadsheet, schemaKey, includeUsers),
    );
  });

  return result;
}

function previewLegacySpreadsheetMigration(sourceSpreadsheetId, includeUsers) {
  var sourceSpreadsheet = openSpreadsheetByIdForMigration_(sourceSpreadsheetId, "Spreadsheet sumber");
  var targetSpreadsheet = getSpreadsheet_();

  if (String(sourceSpreadsheet.getId()) === String(targetSpreadsheet.getId())) {
    throw new Error("Spreadsheet sumber dan target tidak boleh sama.");
  }

  return buildLegacyMigrationResult_(
    sourceSpreadsheet,
    targetSpreadsheet,
    Boolean(includeUsers),
    true,
  );
}

function runLegacySpreadsheetMigration(sourceSpreadsheetId, includeUsers, allowWithoutBackups) {
  var sourceSpreadsheet = openSpreadsheetByIdForMigration_(sourceSpreadsheetId, "Spreadsheet sumber");
  var targetSpreadsheet = getSpreadsheet_();

  if (String(sourceSpreadsheet.getId()) === String(targetSpreadsheet.getId())) {
    throw new Error("Spreadsheet sumber dan target tidak boleh sama.");
  }

  return executeLegacySpreadsheetMigration_(
    sourceSpreadsheet,
    targetSpreadsheet,
    Boolean(includeUsers),
    Boolean(allowWithoutBackups),
  );
}

function migrateLegacySpreadsheetAction_(payload, token) {
  var context = requireSession_(token);
  ensureRole_(context.user, ["admin"]);

  var sourceSpreadsheetId = String((payload && payload.sourceSpreadsheetId) || "").trim();
  var includeUsers = Boolean(payload && payload.includeUsers);
  var dryRun = payload && payload.dryRun !== false;
  var allowWithoutBackups = Boolean(payload && payload.allowWithoutBackups);

  if (!sourceSpreadsheetId) {
    throw new Error("Spreadsheet sumber wajib diisi.");
  }

  if (dryRun) {
    return previewLegacySpreadsheetMigration(sourceSpreadsheetId, includeUsers);
  }

  return runLegacySpreadsheetMigration(sourceSpreadsheetId, includeUsers, allowWithoutBackups);
}
