<?php
/**
 * Geolocation input partial — address autocomplete + hidden lat/lng + detect button.
 *
 * Expected variables:
 *   $geoPrefix      — Field name/id prefix, e.g. "theft_location" or "found_location"
 *   $geoLabel       — Label text for the address field
 *   $geoPlaceholder — Placeholder text
 *   $geoValue       — Current value (optional, default '')
 *   $geoRequired    — Whether the field is required (optional, default true)
 */
$geoValue    = $geoValue ?? '';
$geoRequired = $geoRequired ?? true;
?>
<div class="form-group">
  <label for="<?= e($geoPrefix) ?>_text"><?= e($geoLabel) ?></label>
  <div class="address-autocomplete-wrapper">
    <input type="text" id="<?= e($geoPrefix) ?>_text" name="<?= e($geoPrefix) ?>_text"
           <?= $geoRequired ? 'required' : '' ?>
           placeholder="<?= e($geoPlaceholder) ?>" value="<?= e($geoValue) ?>"
           data-address-autocomplete
           data-lat-input="<?= e($geoPrefix) ?>_lat"
           data-lng-input="<?= e($geoPrefix) ?>_lng"
           data-address-validated="<?= e($geoPrefix) ?>_validated"
           autocomplete="off">
  </div>
</div>

<!-- Hidden GPS + validation fields -->
<input type="hidden" id="<?= e($geoPrefix) ?>_lat" name="<?= e($geoPrefix) ?>_lat">
<input type="hidden" id="<?= e($geoPrefix) ?>_lng" name="<?= e($geoPrefix) ?>_lng">
<input type="hidden" id="<?= e($geoPrefix) ?>_validated" name="address_validated" value="0">

<div class="form-group geo-row">
  <button type="button" class="btn btn-ghost btn-sm" data-geolocate
          data-lat-input="<?= e($geoPrefix) ?>_lat" data-lng-input="<?= e($geoPrefix) ?>_lng"
          data-text-input="<?= e($geoPrefix) ?>_text" data-validated-input="<?= e($geoPrefix) ?>_validated">
    <i data-lucide="map-pin"></i> Zjistit moji polohu
  </button>
  <small class="geo-status text-muted text-sm"></small>
</div>
