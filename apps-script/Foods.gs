function sanitizeFood_(record) {
  return {
    id: record.id,
    supplierId: record.supplier_id,
    supplierName: record.supplier_name_snapshot,
    name: record.food_name,
    unit: record.unit_name,
    defaultPrice: toNumber_(record.default_price),
    isActive: String(record.is_active) !== "false",
    createdAt: record.created_at,
    updatedAt: record.updated_at,
  };
}

function listFoodsAction_(payload, token) {
  var context = requireSession_(token);
  var includeInactive = Boolean(payload && payload.includeInactive) && context.user.role === "admin";
  var items = getSheetRecords_("foods").filter(function (record) {
    return includeInactive || String(record.is_active) !== "false";
  });

  if (payload && payload.supplierId) {
    items = items.filter(function (record) {
      return String(record.supplier_id) === String(payload.supplierId);
    });
  }

  if (payload && (payload.query || payload.search)) {
    var query = normalizeText_(payload.query || payload.search);
    items = items.filter(function (record) {
      return [
        record.food_name,
        record.unit_name,
        record.supplier_name_snapshot,
      ].some(function (part) {
        return normalizeText_(part).indexOf(query) !== -1;
      });
    });
  }

  return items
    .map(sanitizeFood_)
    .sort(function (left, right) {
      var supplierComparison = String(left.supplierName || "").localeCompare(String(right.supplierName || ""));
      if (supplierComparison !== 0) {
        return supplierComparison;
      }

      return String(left.name || "").localeCompare(String(right.name || ""));
    });
}

function saveFoodAction_(payload, token) {
  var context = requireSession_(token);
  ensureRole_(context.user, ["admin"]);

  var now = nowIso_();
  var existing = payload.id ? getRecordById_("foods", payload.id) : null;
  var supplierId = String(payload.supplierId || "").trim();
  var name = String(payload.name || "").trim();
  var unit = String(payload.unit || "").trim();
  var defaultPrice = payload.defaultPrice === "" || payload.defaultPrice === null || payload.defaultPrice === undefined
    ? 0
    : parseNonNegativeNumberStrict_(payload.defaultPrice, "Harga default");

  if (!supplierId) {
    throw new Error("Pemasok wajib dipilih dari master pemasok.");
  }

  var supplierRecord = getRecordById_("suppliers", supplierId);
  if (!supplierRecord) {
    throw new Error("Pemasok tidak ditemukan.");
  }

  if (!name || !unit) {
    throw new Error("Pemasok, nama makanan, dan satuan wajib diisi.");
  }

  var record = existing || {
    id: payload.id || generateId_("FOD"),
    created_at: now,
  };

  record.supplier_id = supplierRecord.id;
  record.supplier_name_snapshot = supplierRecord.supplier_name;
  record.food_name = name;
  record.unit_name = unit;
  record.default_price = defaultPrice;
  record.is_active = payload.isActive === false || payload.isActive === "false" ? "false" : "true";
  record.updated_at = now;

  saveSheetRecord_("foods", withoutMeta_(record), existing ? existing._rowNumber : null);
  return sanitizeFood_(record);
}
