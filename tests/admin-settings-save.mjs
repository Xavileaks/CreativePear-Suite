import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../assets/js/admin-settings-save.js',import.meta.url),'utf8');
function fixture() {
    const handlers={},timers=new Map(); let timerId=0, requests=0, pending;
    const button={value:'Save changes',disabled:false};
    const status={textContent:'',classList:{toggle(){}}};
    const nonce={name:'_wpnonce',type:'hidden',value:'nonce'};
    const text={name:'xw_settings[login][logo_url]',type:'url',value:''};
    const number={name:'xw_settings[login][logo_width]',type:'number',value:'200'};
    const hidden={name:'xw_settings[features][wishlist]',type:'hidden',value:'0'};
    const checkbox={name:hidden.name,type:'checkbox',value:'1',checked:true};
    const form={elements:[nonce,text,number,hidden,checkbox],setAttribute(){},
        querySelector(s){return s==='[type="submit"]'?button:s==='[data-xw-save-status]'?status:nonce;},querySelectorAll(){return [];}};
    class Data { constructor(){this.values=form.elements.filter(c=>c.type!=='checkbox'||c.checked).map(c=>[c.name,c.value]);} entries(){return this.values[Symbol.iterator]();} [Symbol.iterator](){return this.entries();} }
    const $=target=>{if(typeof target==='function'){target();return;}return {on(names,fn){names.split(' ').forEach(n=>handlers[n]=fn);},trigger(name){handlers[name]?.({});}};};
    const window={xwAdminSettings:{ajaxUrl:'/ajax',save:{idle:'idle',pending:'pending',saving:'saving',saved:'saved',error:'error'}},
        fetch:true,addEventListener(name,fn){handlers[name]=fn;},setTimeout(fn){timers.set(++timerId,fn);return timerId;},clearTimeout(id){timers.delete(id);}};
    const fetch=(url,options)=>{requests++; assert.equal(options.body.get('action'),'xw_save_settings'); assert.equal([...options.body].at(-1)[0],'xw_settings_complete');return new Promise((resolve,reject)=>{pending={resolve,reject};});};
    vm.runInNewContext(source,{jQuery:$,window,document:{querySelector(){return form;}},FormData:Data,URLSearchParams,AbortController,fetch});
    const submit=()=>handlers.submit({preventDefault(){}});
    const saved=(width=200)=>pending.resolve({ok:true,json:async()=>({success:true,data:{nonce:'fresh',settings:{login:{logo_url:text.value,logo_width:width},features:{wishlist:checkbox.checked?1:0}}}})});
    return {button,status,text,number,checkbox,handlers,submit,saved,get requests(){return requests;},fail(){pending.reject(new TypeError('network'));}};
}
let f=fixture();
assert.equal(f.button.disabled,true);assert.equal(f.status.textContent,'idle');
f.number.value='210';f.handlers.input();assert.equal(f.button.disabled,false);assert.equal(f.status.textContent,'pending');
f.number.value='200';f.handlers.change();assert.equal(f.button.disabled,true);
console.log('PASS: starts gray/disabled, enables on edits and disables on reverting');
f.number.value='250';f.handlers.input();let saving=f.submit();assert.equal(f.button.disabled,true);assert.equal(f.button.value,'saving');
await f.submit();assert.equal(f.requests,1);f.saved(250);await saving;assert.equal(f.button.disabled,true);assert.equal(f.status.textContent,'saved');
console.log('PASS: AJAX prevents duplicate submits and returns to clean after success');
f.number.value='9999';f.handlers.change();saving=f.submit();f.saved(600);await saving;assert.equal(f.number.value,'600');assert.equal(f.button.disabled,true);
console.log('PASS: sanitized server values are reflected without a reload');
f.number.value='230';f.handlers.change();saving=f.submit();f.number.value='240';f.handlers.input();f.saved(230);await saving;
assert.equal(f.number.value,'240');assert.equal(f.button.disabled,false);assert.equal(f.status.textContent,'pending');
console.log('PASS: edits made in flight stay pending and are never overwritten');
saving=f.submit();f.fail();await saving;assert.equal(f.button.disabled,false);assert.equal(f.status.textContent,'error');assert.equal(f.number.value,'240');
console.log('PASS: network failure preserves edits and enables retry');
let warned=false;f.handlers.beforeunload({preventDefault(){warned=true;}});assert.equal(warned,true);
console.log('PASS: leaving with pending edits warns about data loss');

