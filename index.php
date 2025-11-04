<?php
// index.php (Light modern UI - with Points max % support)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Discount Module — Playtorium</title>

  <!-- SortableJS -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <!-- QRCodeJS -->
  <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">

  <header>
    <h1>Discount Module — Playtorium</h1>
    <p class="muted">Add items & discounts, reorder discounts, calculate totals with VAT, and checkout.</p>
  </header>

  <main class="grid">
    <!-- LEFT: Add Item -->
    <section class="card">
      <h2>Add Item</h2>
      <div class="form-row">
        <input id="item-name" placeholder="Item name" />
        <!--<input id="item-category" placeholder="Category (e.g. Clothing)" />-->
		<input type="text" id="item-category" list="optionsList">
		<datalist id="optionsList">
		  <option value="Clothing"></option>
		  <option value="Accessories"></option>
		  <option value="Electronics"></option>
		  <option value="Others"></option>
		</datalist>
      </div>
      <div class="form-row">
        <input id="item-amount" placeholder="Unit Price (THB)" type="number" min="0" step="0.01" />
        <input id="item-qty" placeholder="Qty" type="number" min="1" step="1" value="1" />
      </div>
      <div class="form-row">
        <button id="add-item-btn" class="btn">Add to Item List</button>
        <button id="clear-items-btn" class="btn ghost">Clear Items</button>
      </div>

      <h3>🛒 Item List</h3>
      <ul id="item-list" class="list"></ul>
    </section>

    <!-- RIGHT: Add Discount -->
    <section class="card">
      <h2>Add Discount</h2>

      <div class="form-row">
        <select id="discount-type">
          <option value="fixed_amount_coupon">Fixed Amount Coupon</option>
          <option value="percentage_coupon">Percentage Coupon</option>
          <option value="percentage_by_category">Percentage by Category</option>
          <option value="discount_by_points">Discount by Points</option>
          <option value="seasonal">Seasonal</option>
        </select>
      </div>

      <!-- Dynamic parameter area -->
      <div id="discount-params"></div>

      <div class="form-row">
        <button id="add-discount-btn" class="btn">Add Discount</button>
        <button id="clear-discounts-btn" class="btn ghost">Clear Discounts</button>
      </div>

      <h3>💸 Discount List (drag to reorder)</h3>
      <ul id="discount-list" class="list"></ul>
    </section>
  </main>

  <!-- CALCULATE / RESULTS -->
  <section class="card controls">
    <div class="row">
      <label>VAT (%) <input id="vat-rate" type="number" value="7" min="0" step="0.01" /></label>
      <!-- <label><input id="include-vat" type="checkbox" checked /> Show include VAT</label>-->
    </div>

    <div class="row">
      <button id="calculate-btn" class="btn primary">Calculate</button>
      <button id="checkout-btn" class="btn success">Checkout (Show QR)</button>
      <button id="export-json-btn" class="btn">Export JSON</button>
    </div>

    <div id="messages"></div>

    <div class="results card">
      <h3>Calculation Result</h3>
      <div id="breakdown"></div>

      <div id="points-max" style="display:none; margin-bottom:8px; color:#374151;">
        <strong>Maximum point discount:</strong> <span id="max-points-value">0</span> THB
      </div>

      <div class="totals">
        <div>Total (Before Discount): <strong id="total-before">—</strong> THB</div>
        <div>VAT <span id="vat-percent"></span>%: <strong id="total-vat-amount">—</strong> THB</div>
        <div>Total (Include VAT): <strong id="total-after">—</strong> THB</div>
      </div>
    </div>

    <div id="qr-area" class="card" style="display:none;">
      <h3>Payment QR</h3>
      <div id="qrcode"></div>
      <p class="muted">Scan to pay — mock payload (replace with real PromptPay format)</p>
    </div>
  </section>

</div>

<script>
function createElem(tag,attrs={},text=''){const e=document.createElement(tag);for(const k in attrs)e.setAttribute(k,attrs[k]);if(text)e.textContent=text;return e;}
function formatMoney(x){return Number(parseFloat(x||0).toFixed(2)).toLocaleString('en-US');}
const msgBox=document.getElementById('messages');
function showMessage(t,cls='info'){msgBox.innerHTML=`<div class="message ${cls}">${t}</div>`;setTimeout(()=>msgBox.innerHTML='',4000);}

/* ---------- Item List ---------- */
let items=[];
function renderItems(){
  const list=document.getElementById('item-list');list.innerHTML='';
  items.forEach((it,i)=>{
    const sub=it.amount*it.qty;
    const li=createElem('li',{'data-i':i});
    li.innerHTML=`<div><strong>${it.name}</strong> — ${it.category} — ${it.qty} pcs × ${formatMoney(it.amount)} = <strong>${formatMoney(sub)}</strong> THB</div>`;
    const b=createElem('button',{'class':'btn tiny ghost'},'Remove');
    b.onclick=()=>{items.splice(i,1);renderItems();};
    li.appendChild(b);
    list.appendChild(li);
  });
}
document.getElementById('add-item-btn').onclick=()=>{
  const n=document.getElementById('item-name').value.trim();
  const c=document.getElementById('item-category').value.trim()||'Uncategorized';
  const a=parseFloat(document.getElementById('item-amount').value);
  const q=parseInt(document.getElementById('item-qty').value)||1;
  if(!n||isNaN(a)||a<0){showMessage('Enter valid item info','error');return;}
  items.push({name:n,category:c,amount:a,qty:q});
  ['item-name','item-category','item-amount'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('item-qty').value=1;
  renderItems();
};
document.getElementById('clear-items-btn').onclick=()=>{if(confirm('Clear all items?')){items=[];renderItems();}};

/* ---------- Discounts ---------- */
let discounts=[];
const listD=document.getElementById('discount-list');
function renderDiscounts(){
  listD.innerHTML='';
  discounts.forEach((d,i)=>{
    const li=createElem('li',{'data-i':i});
    li.innerHTML=`<div><strong>${d.label}</strong><div class="discount-body">${d.summary}</div></div>`;
    const b=createElem('button',{'class':'btn tiny ghost'},'Remove');
    b.onclick=()=>{discounts.splice(i,1);renderDiscounts();};
    li.appendChild(b);
    listD.appendChild(li);
  });
}
new Sortable(listD,{animation:150,onEnd:e=>{const it=discounts.splice(e.oldIndex,1)[0];discounts.splice(e.newIndex,0,it);renderDiscounts();}});

/* ---------- Dynamic Discount Input ---------- */
const typeSel=document.getElementById('discount-type');
const paramsDiv=document.getElementById('discount-params');
function buildParams(){
  const t=typeSel.value;paramsDiv.innerHTML='';
  function row(label,id,type='number',ph='',list=''){const r=createElem('div',{class:'form-row'});r.innerHTML=`<label>${label}<input id="${id}" type="${type}" placeholder="${ph}" list="${list}"></label>`;paramsDiv.appendChild(r);}
  if(t==='fixed_amount_coupon'){
    row('Discount Amount (THB):','param-fixed','number','e.g. 50');
  }else if(t==='percentage_coupon'){
    row('Discount Percentage (%):','param-percent','number','e.g. 10');
  }else if(t==='percentage_by_category'){
	 // <input type="text" id="item-category" list="optionsList">
    row('Category:','param-category','text','e.g. Clothing', 'optionsList');
    //row('Category:','param-category','text','e.g. Clothing');
    row('Discount Percentage (%):','param-percent','number','e.g. 15');
  }else if(t==='discount_by_points'){
    row('Points to Use:','param-points','number','e.g. 60');
    row('Exchange Rate (1 point = ? THB):','param-rate','number','e.g. 1');
    row('Maximum Point % (cap of total price):','param-maxpercent','number','e.g. 20');
  }else if(t==='seasonal'){
    row('Every X THB:','param-every','number','e.g. 300');
    row('Discount Y THB:','param-discount','number','e.g. 40');
  }
}
buildParams();
typeSel.onchange=buildParams;

/* ---------- Add Discount ---------- */
document.getElementById('add-discount-btn').onclick=()=>{
  const t=typeSel.value;
  const d={type:t,params:{},label:'',summary:''};
  if(t==='fixed_amount_coupon'){
    const v=parseFloat(document.getElementById('param-fixed').value);
    if(isNaN(v)){showMessage('Invalid amount','error');return;}
    d.params.amount=v;d.label='Fixed Amount Coupon';d.summary=`${formatMoney(v)} THB off entire cart`;
  }else if(t==='percentage_coupon'){
    const p=parseFloat(document.getElementById('param-percent').value);
    if(isNaN(p)){showMessage('Invalid percentage','error');return;}
    d.params.percent=p;d.label='Percentage Coupon';d.summary=`${p}% off entire cart`;
  }else if(t==='percentage_by_category'){
    const cat=document.getElementById('param-category').value.trim();
    const p=parseFloat(document.getElementById('param-percent').value);
    if(!cat||isNaN(p)){showMessage('Invalid category or percent','error');return;}
    d.params.category=cat;d.params.percent=p;
    d.label='Category Discount';d.summary=`${p}% off "${cat}"`;
  }else if(t==='discount_by_points'){
    const pts=parseFloat(document.getElementById('param-points').value)||0;
    const rate=parseFloat(document.getElementById('param-rate').value)||1;
    const maxp=parseFloat(document.getElementById('param-maxpercent').value)||20;
    d.params.points=pts;d.params.rate=rate;d.params.max_percent=maxp;
    d.label='Points Discount';
    d.summary=`${pts} pts @ ${rate} THB/pt (max ${maxp}% of total)`;
  }else if(t==='seasonal'){
    const e=parseFloat(document.getElementById('param-every').value);
    const y=parseFloat(document.getElementById('param-discount').value);
    if(isNaN(e)||isNaN(y)||e<=0){showMessage('Invalid seasonal inputs','error');return;}
    d.params.every=e;d.params.discount=y;
    d.label='Seasonal Discount';
    d.summary=`Every ${e} THB → -${y} THB`;
  }
  discounts.push(d);renderDiscounts();
};
document.getElementById('clear-discounts-btn').onclick=()=>{if(confirm('Clear all discounts?')){discounts=[];renderDiscounts();}};

/* ---------- Calculate ---------- */
async function calculate(){
  if(items.length===0){showMessage('Add at least one item','error');return;}
  const payload={items,discounts,vat_rate:parseFloat(document.getElementById('vat-rate').value)||0};
  const r=await fetch('calculate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const d=await r.json();
  if(!r.ok){showMessage(d.error||'Server error','error');return;}
  renderResult(d);
}
document.getElementById('calculate-btn').onclick=calculate;

/* ---------- Render Result ---------- */
function renderResult(d){
  const br=document.getElementById('breakdown');br.innerHTML='';
  (d.steps||[]).forEach(s=>{
    const div=createElem('div',{class:'step'});
    div.innerHTML=`<div class="muted">${s.title}</div><div>${s.detail}</div>`;
    br.appendChild(div);
  });

  // display point cap if available
  const pointDiscount = discounts.find(dd => dd.type === 'discount_by_points');
  const maxDiv = document.getElementById('points-max');
  if(pointDiscount){
    const maxp = parseFloat(pointDiscount.params.max_percent)||20;
    const cap = (d.total_before * (maxp/100)).toFixed(2);
    document.getElementById('max-points-value').textContent=formatMoney(cap);
    maxDiv.style.display='block';
  }else{
    maxDiv.style.display='none';
  }

  document.getElementById('total-before').textContent=formatMoney(d.total_before);
  document.getElementById('vat-percent').textContent=d.vat_rate;
  document.getElementById('total-vat-amount').textContent=formatMoney(d.vat_amount);
  document.getElementById('total-after').textContent=formatMoney(d.total_after);
  document.getElementById('qr-area').style.display='none';
}

/* ---------- Checkout QR ---------- */
document.getElementById('checkout-btn').onclick=async()=>{
  await calculate();
  const amt=parseFloat(document.getElementById('total-after').textContent.replace(/,/g,''))||0;
  const payload={amount:amt,currency:'THB',desc:'Order Payment',ts:new Date().toISOString()};
  const q=document.getElementById('qrcode');q.innerHTML='';new QRCode(q,{text:JSON.stringify(payload),width:220,height:220});
  document.getElementById('qr-area').style.display='block';
};

/* ---------- Export JSON ---------- */
document.getElementById('export-json-btn').onclick=()=>{
  const payload={items,discounts,vat_rate:parseFloat(document.getElementById('vat-rate').value)||0};
  const blob=new Blob([JSON.stringify(payload,null,2)],{type:'application/json'});
  const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='discount_payload.json';a.click();URL.revokeObjectURL(url);
};
</script>
</body>
</html>
