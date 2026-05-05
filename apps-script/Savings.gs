function sanitizeSaving_(record) {
  return {
    id: record.id,
    studentId: record.student_id,
    studentName: record.student_name,
    className: record.class_name,
    gender: record.gender,
    groupName: record.group_name,
    depositAmount: toNumber_(record.deposit_amount),
    changeBalance: toNumber_(record.change_balance),
    recordedAt: record.recorded_at,
    recordedByUserId: record.recorded_by_user_id,
    recordedByName: record.recorded_by_name,
    notes: record.notes,
    createdAt: record.created_at,
    updatedAt: record.updated_at,
    deletedAt: record.deleted_at || "",
  };
}

function listSavingsAction_(token) {
  requireSession_(token);
  return getSheetRecords_("savings")
    .filter(function (record) {
      return !record.deleted_at;
    })
    .map(sanitizeSaving_)
    .sort(function (left, right) {
      return left.studentName.localeCompare(right.studentName);
    });
}

function ensureSavingAccess_(record, context) {
  if (!record) {
    throw new Error("Data simpanan tidak ditemukan.");
  }

  if (context.user.role !== "admin" && String(record.recorded_by_user_id) !== String(context.user.id)) {
    throw new Error("Data simpanan ini bukan milik Anda.");
  }
}

function saveSavingAction_(payload, token) {
  var context = requireSession_(token);
  ensureRole_(context.user, ["admin", "petugas"]);

  var now = nowIso_();
  var existing = payload.id ? getRecordById_("savings", payload.id) : null;

  if (existing) {
    ensureSavingAccess_(existing, context);
  }

  if (!payload.studentName || !payload.className || !payload.recordedAt) {
    throw new Error("Nama siswa, kelas, dan waktu pencatatan wajib diisi.");
  }

  var record = existing || {
    id: payload.id || generateId_("SAV"),
    created_at: now,
    recorded_by_user_id: context.user.id,
    recorded_by_name: context.user.full_name,
    deleted_at: "",
  };

  record.student_id = String(payload.studentId || "").trim();
  record.student_name = String(payload.studentName || "").trim();
  record.class_name = String(payload.className || "").trim();
  record.gender = String(payload.gender || "").trim();
  record.group_name = String(payload.groupName || "").trim();
  record.deposit_amount = parseNonNegativeNumberStrict_(payload.depositAmount, "Jumlah setoran");
  record.change_balance = parseNonNegativeNumberStrict_(payload.changeBalance, "Saldo kembalian");
  record.recorded_at = String(payload.recordedAt || "").trim();
  record.notes = String(payload.notes || "").trim();
  record.updated_at = now;
  record.deleted_at = "";

  saveSheetRecord_("savings", withoutMeta_(record), existing ? existing._rowNumber : null);
  return sanitizeSaving_(record);
}

function deleteSavingAction_(payload, token) {
  var context = requireSession_(token);
  ensureRole_(context.user, ["admin", "petugas"]);

  var record = getRecordById_("savings", payload.id);
  ensureSavingAccess_(record, context);
  record.deleted_at = nowIso_();
  record.updated_at = record.deleted_at;
  saveSheetRecord_("savings", withoutMeta_(record), record._rowNumber);

  return sanitizeSaving_(record);
}
