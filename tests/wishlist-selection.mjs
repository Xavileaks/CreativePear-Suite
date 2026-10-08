import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../assets/wishlist/wishlist.js', import.meta.url), 'utf8')
    .replace('async function init(root)', 'window.__selectionTest = {selectedProduct, selectedItem, setOwn(data) { own = data; }};\n    async function init(root)');
const inputs = {variation: {value:'24'}, size:{name:'attribute_size',value:'X-Large'}, color:{name:'attribute_color',value:'Burgundy'}};
const form = {dataset:{product_id:'23'}, querySelector() {return inputs.variation;}, querySelectorAll() {return [inputs.size,inputs.color];}};
const document = {readyState:'loading',documentElement:{},addEventListener(){},querySelectorAll(){return [form];}};
const window = {xwWishlist:{},location:{href:'https://shop.example/product/',origin:'https://shop.example'},addEventListener(){}};
const context = vm.createContext({window,document,URL,URLSearchParams,MutationObserver:class {observe(){}}});
vm.runInContext(source,context);
const api=window.__selectionTest;
function button(single=true, id='23', localForms=[]) {
    const root={querySelector(){return {dataset:{xwWlProductContext:single?'single':'loop'}};},querySelectorAll(){return localForms;}};
    return {dataset:{xwWlAdd:id},closest(selector){return selector==='[data-xw-wl]'?root:null;}};
}
let selection=api.selectedProduct(button());
assert.equal(selection.id,24); assert.equal(selection.variationId,24); assert.equal(selection.attributes.attribute_color,'Burgundy');
api.setOwn({items:[{id:23,key:'23',attributes:{}},{id:24,key:'selected',attributes:{attribute_size:'X-Large',attribute_color:'Burgundy'}}]});
assert.equal(api.selectedItem(selection).key,'selected');
inputs.color.value='Black'; assert.equal(api.selectedItem(api.selectedProduct(button())),undefined);
console.log('PASS: current selection overrides parent and distinguishes saved combinations');
assert.equal(api.selectedProduct(button(false)).id,23); assert.equal(api.selectedProduct(button(true,'99')).id,99);
console.log('PASS: loop and unrelated products do not inherit a single-product selection');
inputs.variation.value='0'; assert.equal(api.selectedProduct(button()).id,23); assert.equal(api.selectedItem(api.selectedProduct(button())).key,'23');
console.log('PASS: clearing selection returns to the original parent wishlist state');
inputs.variation.value='26'; api.setOwn({items:[{id:26,key:'small',attributes:{attribute_size:'Small',attribute_color:'Black'}},{id:26,key:'large',attributes:{attribute_size:'X-Large',attribute_color:'Black'}}]});
assert.equal(api.selectedItem(api.selectedProduct(button())).key,'large'); inputs.size.value='Small';
assert.equal(api.selectedItem(api.selectedProduct(button())).key,'small');
console.log('PASS: two choices of the same Any-size variation retain independent button states');
