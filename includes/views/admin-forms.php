<?php
function plant_form($plant)
{
    $id = $plant['id'] ?? 0;
    $return = 'pages/admin/dashboard.php?view=plant_form' . ($id ? '&id=' . $id : '');
?><section class="panel form-panel">
        <h2><?= $id ? 'Edit plant' : 'Add a new plant' ?></h2>
        <p>Set stock, price, care instructions, and the watering interval for customer reminders.</p><?php post_form('plant_save', $return, '', true);
                                                                                                        if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
        <div class="form-grid"><label class="full">Plant name<input name="name" value="<?= h(old('name', $plant['name'] ?? '')) ?>" maxlength="150" required></label><label>Category<select name="category" required><?php foreach (['indoor', 'outdoor', 'fruit', 'flower'] as $category): ?><option value="<?= $category ?>" <?= old('category', $plant['category'] ?? 'indoor') === $category ? 'selected' : '' ?>><?= ucfirst($category) ?></option><?php endforeach; ?></select></label><label>Price (BDT)<input name="price" type="number" min="0" max="99999999" step="0.01" value="<?= h(old('price', $plant['price'] ?? '')) ?>" required></label><label>Stock quantity<input name="stock" type="number" min="0" max="1000000" value="<?= h(old('stock', $plant['stock'] ?? 0)) ?>" required></label><label>Watering interval (hours)<input name="watering_hours" type="number" min="1" max="8760" value="<?= h(old('watering_hours', $plant['watering_hours'] ?? 168)) ?>" required><small>Example: 24 = daily, 168 = weekly.</small></label><label class="full">Description<textarea name="description" maxlength="5000" required><?= h(old('description', $plant['description'] ?? '')) ?></textarea></label><label class="full">Care instructions<textarea name="care_instructions" maxlength="5000" required><?= h(old('care_instructions', $plant['care_instructions'] ?? '')) ?></textarea></label>
            <?php foreach (['care_light' => 'Light needs', 'care_water' => 'Watering advice', 'care_soil' => 'Soil needs'] as $key => $label): ?><label><?= $label ?><input name="<?= $key ?>" maxlength="80" value="<?= h(old($key, $plant[$key] ?? '')) ?>"></label><?php endforeach; ?>
            <label>Plant photo<input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>Optional JPG, PNG or WebP, max 5 MB.</small></label><label class="full">Care guide PDF<input type="file" name="care_file" accept="application/pdf"><small>Optional PDF, max 5 MB. Existing files stay when no replacement is selected.</small></label><label class="full"><input type="checkbox" name="active" value="1" <?= (isset($_SESSION['old']) ? isset($_SESSION['old']['active']) : ($plant['active'] ?? 1)) ? 'checked' : '' ?>>Visible in the plant catalog</label>
        </div>
        <div class="form-actions"><a class="btn secondary" href="<?= h(url('pages/admin/dashboard.php?view=plants')) ?>">Cancel</a><button class="btn"><?= $id ? 'Save plant' : 'Add plant' ?></button></div>
        </form>
    </section>
<?php }
function staff_form($staff)
{
    $id = $staff['id'] ?? 0;
    $zones = ['Dhaka North', 'Dhaka South', 'Gulshan / Banani', 'Dhanmondi / Lalmatia', 'Nursery Floor'];
    $assigned_zone = old('zone', $staff['zone'] ?? '');
    if ($assigned_zone !== '' && !in_array($assigned_zone, $zones)) $zones[] = $assigned_zone;
    $return = 'pages/admin/dashboard.php?view=staff_form' . ($id ? '&id=' . $id : '');
?><section class="panel form-panel">
        <h2><?= $id ? 'Edit staff member' : 'Add New Staff Member' ?></h2>
        <p>Complete the fields to <?= $id ? 'update this account.' : 'create a new account.' ?></p><?php post_form('staff_save', $return, '', true);
                                                                                                    if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?><div class="form-grid">
            <label class="full">Full name<input name="name" maxlength="120" value="<?= h(old('name', $staff['name'] ?? '')) ?>" required></label><label>Role / position<select name="role" required><?php foreach (['gardener' => 'Gardener', 'staff' => 'Nursery staff'] as $key => $label): ?><option value="<?= $key ?>" <?= old('role', $staff['role'] ?? 'gardener') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label><label>Phone number<input type="tel" name="phone" maxlength="11" pattern="01[3-9][0-9]{8}" value="<?= h(old('phone', $staff['phone'] ?? '')) ?>" required></label><label class="full">Email address<input type="email" name="email" maxlength="190" value="<?= h(old('email', $staff['email'] ?? '')) ?>" required></label><label>Assigned zone<select name="zone">
                    <option value="">Not assigned</option><?php foreach ($zones as $zone): ?><option <?= old('zone', $staff['zone'] ?? '') === $zone ? 'selected' : '' ?>><?= h($zone) ?></option><?php endforeach; ?>
                </select></label><label>Salary (BDT)<input name="salary" type="number" min="0" max="99999999" step="0.01" value="<?= h(old('salary', $staff['salary'] ?? 0)) ?>" required></label><label>Duty status<select name="status"><?php foreach (['Available', 'On Visit', 'Active Desk', 'On Leave', 'Inactive'] as $status): ?><option <?= old('status', $staff['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label><label><?= $id ? 'New password (leave blank to keep)' : 'Temporary password' ?><input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $id ? '' : 'required' ?>></label>
            <label class="full">NID document (PDF)<input type="file" name="nid_file" accept="application/pdf"><small>Optional PDF, max 5 MB. <?= $id ? 'Leave blank to keep the current document.' : 'Only admins can download this document.' ?></small><?php if ($id && !empty($staff['nid_file_path'])): ?><a href="<?= h(url('pages/admin/nid.php?id=' . $id)) ?>">Download current NID</a><?php endif; ?></label>
        </div>
        <div class="form-actions"><a class="btn secondary" href="<?= h(url('pages/admin/dashboard.php?view=staff')) ?>">Cancel</a><button class="btn"><?= $id ? 'Save changes' : 'Add Staff Member' ?></button></div>
        </form>
    </section>
<?php }
