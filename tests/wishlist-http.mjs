// Isolated local WordPress only. Run after wishlist-integration.php, while PHP serves it.
import assert from 'node:assert/strict';
const base = process.argv[2] || 'http://127.0.0.1:8097';
assert(['127.0.0.1', 'localhost', '[::1]'].includes(new URL(base).hostname), 'local site required');
const ids = (process.argv[3] || '11,12,13,14,15').split(',').map(Number);
function client() {
    const cookies = new Map();
    let nonce = '';
    return async (operation, fields = {}, token = null) => {
        const body = new URLSearchParams({action:'xw_wishlist',operation});
        if (operation !== 'state') body.set('nonce', token ?? nonce);
        for (const [key, value] of Object.entries(fields)) {
            if (Array.isArray(value)) value.forEach(v => body.append(`${key}[]`, String(v)));
            else body.set(key, String(value));
        }
        const response = await fetch(`${base}/wp-admin/admin-ajax.php`, {method:'POST',body,headers:{cookie:[...cookies].map(([k,v])=>`${k}=${v}`).join('; ')}});
        for (const cookie of response.headers.getSetCookie()) {
            const [pair] = cookie.split(';'); const split = pair.indexOf('=');
            cookies.set(pair.slice(0,split),pair.slice(split+1));
        }
        const json = await response.json();
        if (json.data?.nonce) nonce = json.data.nonce;
        return {status:response.status, ...json};
    };
}
const a=client(), b=client();
const initialA=await a('state'), initialB=await b('state');
assert.equal(initialA.data.count,0); assert.equal(initialB.data.count,0);
assert.notEqual(initialA.data.nonce, initialB.data.nonce); console.log('PASS: guests are isolated with different nonces');
assert.equal((await b('add',{product_id:ids[0]},initialA.data.nonce)).status,403); console.log('PASS: other visitor nonce rejected');
assert.equal((await a('add',{product_id:ids[0]},'bad')).status,403); console.log('PASS: invalid nonce rejected');
assert.equal((await a('add',{product_id:ids[4]})).status,404); console.log('PASS: private product rejected');
assert.equal((await a('add',{product_id:ids[0]})).data.count,1);
assert.equal((await a('add',{product_id:ids[0]})).data.count,1); console.log('PASS: repeated add idempotent');
assert.equal((await b('state')).data.count,0); console.log('PASS: another visitor cannot see private wishlist');
assert.equal((await a('add',{product_id:ids[1]})).data.count,2);
assert.equal((await a('add',{product_id:ids[2]})).data.count,3);
const share=await a('share'); assert.equal(share.success,true);
const token=new URL(share.data.url).searchParams.get('xw_wishlist'); assert.match(token,/^[a-f0-9]{64}$/);
const shared=await b('state',{share:token}); assert.equal(shared.data.count,3); assert.equal(shared.data.read_only,true);
assert.equal((await b('remove',{product_id:ids[0],share:token})).data.count,0);
assert.equal((await a('state')).data.count,3); console.log('PASS: shared view does not grant write access');
assert.equal((await b('state',{share:'invalid'})).status,404); console.log('PASS: invalid sharing token rejected');
const cart=await a('cart',{ids:[ids[0],ids[1],ids[2],ids[3]]});
assert.equal(cart.data.added,2); assert.equal(cart.data.skipped,2); console.log('PASS: bulk cart accepts simple products only and validates membership');
assert.equal((await b('cart',{ids:[ids[0]]})).data.added,0); console.log('PASS: cart cannot bypass wishlist ownership');
assert.equal((await a('remove',{product_id:ids[0]})).data.count,2); console.log('PASS: remove persists');
assert.equal((await a('state')).data.count,2); console.log('PASS: subsequent state restores list');
