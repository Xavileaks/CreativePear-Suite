import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const code = fs.readFileSync(new URL('../assets/js/elementor-date.js', import.meta.url), 'utf8');
const context = { module: { exports: {} } };
vm.runInNewContext(code, context);
const { mask, toISO, isoToDisplay } = context.module.exports;
for (const [typed, expected] of [['', ''], ['2', '2'], ['22', '22'], ['221', '22/1'], ['2210', '22/10'], ['22102026', '22/10/2026'], ['22/10/2026', '22/10/2026'], ['2026-10-22', '22/10/2026'], ['abc22102026999', '22/10/2026']]) assert.equal(mask(typed), expected);
for (const [display, iso] of [['29/02/2024', '2024-02-29'], ['22/10/2026', '2026-10-22'], ['01/01/0001', '0001-01-01'], ['31/12/9999', '9999-12-31']]) { assert.equal(toISO(display), iso); assert.equal(isoToDisplay(iso), display); }
for (const invalid of ['', '22/10', '31/02/2026', '29/02/2025', '31/04/2026', '00/10/2026', '01/00/2026', '01/13/2026', '01/01/0000', '12/12/26', '2026-10-22']) assert.equal(toISO(invalid), '');
assert.ok(!code.includes('drayenicaleon') && !code.includes('form-field-fecha'), 'no domain or field ID is hardcoded');
console.log('PASS: numeric mask, ISO paste, leap years, invalid/partial dates, canonical serialization and general selector');
