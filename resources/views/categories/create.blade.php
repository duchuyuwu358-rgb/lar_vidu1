<div class="mb-4">
    <label class="form-label fw-bold">Danh Sách Màu Sắc Hỗ Trợ</label>
    <p class="text-muted small mb-3">Tích chọn các màu sắc khả dụng cho các sản phẩm thuộc danh mục này:</p>
    
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <!-- Đen -->
        <input type="checkbox" class="btn-check" id="color_black" name="colors[]" value="black" autocomplete="off" {{ (is_array(old('colors', $category->colors ?? [])) && in_array('black', old('colors', $category->colors ?? []))) ? 'checked' : '' }}>
        <label class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2" for="color_black">
            <span class="rounded-circle d-inline-block" style="width: 14px; height: 14px; background-color: #000000;"></span>
            <span class="fw-semibold text-dark">Đen</span>
        </label>

        <!-- Bạc -->
        <input type="checkbox" class="btn-check" id="color_silver" name="colors[]" value="silver" autocomplete="off" {{ (is_array(old('colors', $category->colors ?? [])) && in_array('silver', old('colors', $category->colors ?? []))) ? 'checked' : '' }}>
        <label class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2" for="color_silver">
            <span class="rounded-circle d-inline-block" style="width: 14px; height: 14px; background-color: #c0c0c0;"></span>
            <span class="fw-semibold text-dark">Bạc</span>
        </label>

        <!-- Trắng (Thêm viền mỏng) -->
        <input type="checkbox" class="btn-check" id="color_white" name="colors[]" value="white" autocomplete="off" {{ (is_array(old('colors', $category->colors ?? [])) && in_array('white', old('colors', $category->colors ?? []))) ? 'checked' : '' }}>
        <label class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2" for="color_white">
            <span class="rounded-circle d-inline-block border border-secondary-subtle" style="width: 14px; height: 14px; background-color: #ffffff;"></span>
            <span class="fw-semibold text-dark">Trắng</span>
        </label>

        <!-- Xám -->
        <input type="checkbox" class="btn-check" id="color_grey" name="colors[]" value="grey" autocomplete="off" {{ (is_array(old('colors', $category->colors ?? [])) && in_array('grey', old('colors', $category->colors ?? []))) ? 'checked' : '' }}>
        <label class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2" for="color_grey">
            <span class="rounded-circle d-inline-block" style="width: 14px; height: 14px; background-color: #6c757d;"></span>
            <span class="fw-semibold text-dark">Xám</span>
        </label>

        <!-- Vàng Đồng -->
        <input type="checkbox" class="btn-check" id="color_gold" name="colors[]" value="gold" autocomplete="off" {{ (is_array(old('colors', $category->colors ?? [])) && in_array('gold', old('colors', $category->colors ?? []))) ? 'checked' : '' }}>
        <label class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2" for="color_gold">
            <span class="rounded-circle d-inline-block" style="width: 14px; height: 14px; background-color: #d4af37;"></span>
            <span class="fw-semibold text-dark">Vàng Đồng</span>
        </label>
    </div>
</div>