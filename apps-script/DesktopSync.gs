function maxIsoTimestamp_(left, right) {
  return String(left || "") > String(right || "") ? String(left || "") : String(right || "");
}

function getRecordSyncTimestamp_(record) {
  return maxIsoTimestamp_(
    maxIsoTimestamp_(record.updated_at, record.created_at),
    record.deleted_at
  );
}

function isRecordUpdatedSince_(record, sinceValue) {
  if (!sinceValue) return true;
  return String(getRecordSyncTimestamp_(record)) > String(sinceValue);
}

function buildSheetCursor_(schemaKey) {
  return getSheetRecords_(schemaKey).reduce(function (cursor, record) {
    return maxIsoTimestamp_(cursor, getRecordSyncTimestamp_(record));
  }, "");
}

function filterSyncRecordsByRole_(context, schemaKey, records) {
  if (context.user.role === "admin") {
    return records;
  }

  switch (schemaKey) {
    case "users":
      return records.filter(function (record) {
        return String(record.id) === String(context.user.id);
      });
    case "transactions":
      return records.filter(function (record) {
        return String(record.input_by_user_id) === String(context.user.id);
      });
    case "daily_finance":
      return records.filter(function (record) {
        return String(record.created_by_user_id) === String(context.user.id);
      });
    case "change_entries":
      return records.filter(function (record) {
        return String(record.created_by_user_id) === String(context.user.id);
      });
    case "supplier_payouts":
      return [];
    default:
      return records;
  }
}

function getSyncEntityConfig_(entityType) {
  switch (entityType) {
    case "user":
      return {
        schemaKey: "users",
        sanitizer: sanitizeUser_,
      };
    case "supplier":
      return {
        schemaKey: "suppliers",
        sanitizer: sanitizeSupplier_,
      };
    case "buyer":
      return {
        schemaKey: "buyers",
        sanitizer: sanitizeBuyer_,
      };
    case "transaction":
      return {
        schemaKey: "transactions",
        sanitizer: sanitizeTransaction_,
      };
    case "saving":
      return {
        schemaKey: "savings",
        sanitizer: sanitizeSaving_,
      };
    case "dailyFinance":
      return {
        schemaKey: "daily_finance",
        sanitizer: sanitizeDailyFinance_,
      };
    case "changeEntry":
      return {
        schemaKey: "change_entries",
        sanitizer: sanitizeChangeEntry_,
      };
    case "supplierPayout":
      return {
        schemaKey: "supplier_payouts",
        sanitizer: sanitizeSupplierPayout_,
      };
    default:
      throw new Error("Entity sync tidak dikenal: " + entityType);
  }
}

function getSyncServerRecord_(entityType, entityId) {
  if (!entityId) {
    return null;
  }

  var config = getSyncEntityConfig_(entityType);
  var record = getRecordById_(config.schemaKey, entityId);

  if (!record) {
    return null;
  }

  return config.sanitizer(record);
}

function syncMutationResult_(clientMutationId, status, message, serverRecord) {
  return {
    clientMutationId: clientMutationId,
    status: status,
    message: message || "",
    serverRecord: serverRecord || null,
  };
}

function executeSyncMutation_(mutation, token) {
  var action = String((mutation && mutation.action) || "");
  var payload = (mutation && mutation.payload) || {};

  switch (action) {
    case "saveUser":
      return saveUserAction_(payload, token);
    case "saveSupplier":
      return saveSupplierAction_(payload, token);
    case "saveTransaction":
      return saveTransactionAction_(payload, token);
    case "deleteTransaction":
      return deleteTransactionAction_(payload, token);
    case "saveDailyFinance":
      return saveDailyFinanceAction_(payload, token);
    case "deleteDailyFinance":
      return deleteDailyFinanceAction_(payload, token);
    case "updateChangeEntryStatus":
      return updateChangeEntryStatusAction_(payload, token);
    case "settleSupplierPayout":
      return settleSupplierPayoutAction_(payload, token);
    case "saveSaving":
      return saveSavingAction_(payload, token);
    case "deleteSaving":
      return deleteSavingAction_(payload, token);
    default:
      throw new Error("Action sync tidak dikenal: " + action);
  }
}

function syncPushAction_(payload, token) {
  requireSession_(token);

  var mutations = (payload && payload.mutations) || [];

  return {
    results: mutations.map(function (mutation) {
      var clientMutationId = String((mutation && mutation.clientMutationId) || "");
      var entityType = String((mutation && mutation.entityType) || "");
      var entityId = String((mutation && mutation.entityId) || (mutation && mutation.payload && mutation.payload.id) || "");
      var expectedUpdatedAt = String((mutation && mutation.expectedUpdatedAt) || "");

      try {
        var currentRecord = getSyncServerRecord_(entityType, entityId);

        if (expectedUpdatedAt && currentRecord && String(currentRecord.updatedAt || currentRecord.createdAt || "") !== expectedUpdatedAt) {
          return syncMutationResult_(
            clientMutationId,
            "conflict",
            "Data server berubah sejak perubahan lokal dibuat.",
            currentRecord,
          );
        }

        executeSyncMutation_(mutation, token);

        var latestRecord = getSyncServerRecord_(entityType, entityId || (mutation && mutation.payload && mutation.payload.id));
        return syncMutationResult_(clientMutationId, "applied", "Perubahan berhasil diterapkan.", latestRecord);
      } catch (error) {
        return syncMutationResult_(
          clientMutationId,
          "failed",
          error.message,
          getSyncServerRecord_(entityType, entityId),
        );
      }
    }),
  };
}

function syncPullAction_(payload, token) {
  var context = requireSession_(token);

  var since = (payload && payload.since) || {};

  var users = filterSyncRecordsByRole_(context, "users", getSheetRecords_("users"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.users);
    })
    .map(sanitizeUser_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  var buyers = filterSyncRecordsByRole_(context, "buyers", getSheetRecords_("buyers"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.buyers);
    })
    .map(sanitizeBuyer_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  var savings = filterSyncRecordsByRole_(context, "savings", getSheetRecords_("savings"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.savings);
    })
    .map(sanitizeSaving_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  var suppliers = filterSyncRecordsByRole_(context, "suppliers", getSheetRecords_("suppliers"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.suppliers);
    })
    .map(sanitizeSupplier_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  var transactions = filterSyncRecordsByRole_(context, "transactions", getSheetRecords_("transactions"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.transactions);
    })
    .map(sanitizeTransaction_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  var dailyFinance = filterSyncRecordsByRole_(context, "daily_finance", getSheetRecords_("daily_finance"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.dailyFinance);
    })
    .map(sanitizeDailyFinance_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  var changeEntries = filterSyncRecordsByRole_(context, "change_entries", getSheetRecords_("change_entries"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.changeEntries);
    })
    .map(sanitizeChangeEntry_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  var supplierPayouts = filterSyncRecordsByRole_(context, "supplier_payouts", getSheetRecords_("supplier_payouts"))
    .filter(function (record) {
      return isRecordUpdatedSince_(record, since.supplierPayouts);
    })
    .map(sanitizeSupplierPayout_)
    .sort(function (left, right) {
      return String(right.updatedAt).localeCompare(String(left.updatedAt));
    });

  return {
    users: users,
    buyers: buyers,
    savings: savings,
    suppliers: suppliers,
    transactions: transactions,
    dailyFinance: dailyFinance,
    changeEntries: changeEntries,
    supplierPayouts: supplierPayouts,
    cursors: {
      users: buildSheetCursor_("users"),
      buyers: buildSheetCursor_("buyers"),
      savings: buildSheetCursor_("savings"),
      suppliers: buildSheetCursor_("suppliers"),
      transactions: buildSheetCursor_("transactions"),
      dailyFinance: buildSheetCursor_("daily_finance"),
      changeEntries: buildSheetCursor_("change_entries"),
      supplierPayouts: buildSheetCursor_("supplier_payouts"),
    },
    serverTime: nowIso_(),
  };
}
