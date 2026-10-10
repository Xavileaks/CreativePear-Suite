import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../assets/js/admin-settings-save.js',import.meta.url),'utf8');
function fixture(withEditor = false, delayedEditor = false) {
    const handlers={},timers=new Map(); let timerId=0, requests=0, pending;
    const button={value:'Save changes',disabled:false};
    const status={textContent:'',classList:{toggle(){}}};
    const nonce={name:'_wpnonce',type:'hidden',value:'nonce'};
    const text={name:'xw_settings[login][logo_url]',type:'url',value:''};
    const number={name:'xw_settings[login][logo_width]',type:'number',value:'200'};
    const hidden={name:'xw_settings[features][wishlist]',type:'hidden',value:'0'};
    const checkbox={name:hidden.name,type:'checkbox',value:'1',checked:true};
    const footer={name:'xw_settings[admin_branding][footer_html]',type:'textarea',value:'Original footer'};
    const editorHandlers={};
    let editorContent = 'Original footer', editorDirty = false;
    const editor={initialized:!delayedEditor,getElement(){return footer;},isHidden(){return false;},getContent(){return editorContent;},
        setContent(value){editorContent=value;editorDirty=true;editorHandlers.SetContent?.();},
        save(){footer.value=editorContent;editorDirty=false;},setDirty(value){editorDirty=value;},
        on(names,fn){names.split(' ').forEach(n=>editorHandlers[n]=fn);}};
    const form={elements:[nonce,text,number,hidden,checkbox,...(withEditor?[footer]:[])],setAttribute(){},contains(c){return this.elements.includes(c);},
        querySelector(s){return s==='[type="submit"]'?button:s==='[data-xw-save-status]'?status:nonce;},querySelectorAll(){return [];}};
    class Data { constructor(){this.values=form.elements.filter(c=>c.type!=='checkbox'||c.checked).map(c=>[c.name,c.value]);} set(name,value){this.values=this.values.filter(e=>e[0]!==name);this.values.push([name,value]);} entries(){return this.values[Symbol.iterator]();} [Symbol.iterator](){return this.entries();} }
    const $=target=>{if(typeof target==='function'){target();return;}return {on(names,fn){names.split(' ').forEach(n=>handlers[n]=fn);},trigger(name){handlers[name]?.({});}};};
    const window={xwAdminSettings:{ajaxUrl:'/ajax',save:{idle:'idle',pending:'pending',saving:'saving',saved:'saved',error:'error'}},
        fetch:true,addEventListener(name,fn){handlers[name]=fn;},setTimeout(fn){timers.set(++timerId,fn);return timerId;},clearTimeout(id){timers.delete(id);}};
    if(withEditor) window.tinymce={editors:[editor],on(){}};
    let submittedFooter;
    const fetch=(url,options)=>{requests++;submittedFooter=options.body.get(footer.name); assert.equal(options.body.get('action'),'xw_save_settings'); assert.equal([...options.body].at(-1)[0],'xw_settings_complete');return new Promise((resolve,reject)=>{pending={resolve,reject};});};
    vm.runInNewContext(source,{jQuery:$,window,document:{querySelector(){return form;}},FormData:Data,URLSearchParams,AbortController,fetch});
    const submit=()=>handlers.submit({preventDefault(){}});
    const saved=(width=200,footerValue=submittedFooter)=>pending.resolve({ok:true,json:async()=>({success:true,data:{nonce:'fresh',settings:{login:{logo_url:text.value,logo_width:width},features:{wishlist:checkbox.checked?1:0},...(withEditor?{admin_branding:{footer_html:footerValue}}:{})}}})});
    return {button,status,text,number,checkbox,footer,editor,handlers,submit,saved,get requests(){return requests;},get submittedFooter(){return submittedFooter;},get editorDirty(){return editorDirty;},
        editFooter(value){editorContent=value;editorDirty=true;editorHandlers.input?.();},
        initializeEditor(value){editorContent=value;editor.initialized=true;editorHandlers.init?.();},fail(){pending.reject(new TypeError('network'));}};
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

f=fixture(true);
f.editFooter('Edited footer'); assert.equal(f.button.disabled,false);
saving=f.submit();assert.equal(f.submittedFooter,'Edited footer');f.saved();await saving;
assert.equal(f.editorDirty,false);assert.equal(f.button.disabled,true);
// TinyMCE's native BeforeUnload saves raw paragraphs, while WP's normal
// getContent strips them. This must not make a successfully saved form dirty.
f.footer.value='<p>Edited footer</p>';
warned=false;f.handlers.beforeunload({preventDefault(){warned=true;}});assert.equal(warned,false);
console.log('PASS: raw TinyMCE textarea rewrite after AJAX save does not trigger a false exit warning');
f.editFooter('Unsaved editor content');warned=false;f.handlers.beforeunload({preventDefault(){warned=true;}});assert.equal(warned,true);
saving=f.submit();f.fail();await saving;assert.equal(f.editorDirty,true);assert.equal(f.button.disabled,false);
console.log('PASS: editor changes and failed saves still warn and remain available for retry');
saving=f.submit();f.editFooter('New edit during save');f.saved();await saving;
assert.equal(f.editor.getContent(),'New edit during save');assert.equal(f.editorDirty,true);assert.equal(f.button.disabled,false);
console.log('PASS: editor edits made during AJAX are not overwritten or marked saved');
f=fixture(true,true);f.number.value='220';f.handlers.input();f.initializeEditor('Formatted initial footer');
assert.equal(f.button.disabled,false);f.number.value='200';f.handlers.input();assert.equal(f.button.disabled,true);
console.log('PASS: delayed editor initialization does not create false edits or erase real pending changes');
