// app.js - deteksi warna dominan + nama warna terdekat (sinkron dgn config.php)
const COLOR_REF = [
  ["Hitam","#000000"],["Putih","#FFFFFF"],["Abu-abu","#9E9E9E"],
  ["Merah","#E53935"],["Marun","#7B1E1E"],["Orange","#FB8C00"],
  ["Kuning","#FDD835"],["Krem","#F5E6C8"],["Hijau","#43A047"],
  ["Toska","#00ACC1"],["Biru","#1E88E5"],["Navy","#1A237E"],
  ["Ungu","#8E24AA"],["Pink","#EC407A"],["Coklat","#6D4C41"]
];
function hexToRgb(hex){
  hex = hex.replace('#','');
  if(hex.length===3) hex = hex.split('').map(c=>c+c).join('');
  return [parseInt(hex.substr(0,2),16),parseInt(hex.substr(2,2),16),parseInt(hex.substr(4,2),16)];
}
function rgbToHex(r,g,b){
  return '#'+[r,g,b].map(v=>Math.max(0,Math.min(255,Math.round(v))).toString(16).padStart(2,'0')).join('').toUpperCase();
}
function nearestColorName(hex){
  const [r,g,b] = hexToRgb(hex);
  let best=null,bd=1e12;
  for(const [n,h] of COLOR_REF){
    const [r2,g2,b2]=hexToRgb(h);
    const d=(r-r2)**2+(g-g2)**2+(b-b2)**2;
    if(d<bd){bd=d;best=n;}
  }
  return best;
}
// Ambil warna dominan area TENGAH foto (abaikan pinggir/background)
function dominantColorOf(img){
  const c=document.createElement('canvas');
  const W=120,H=120; c.width=W; c.height=H;
  const x=c.getContext('2d');
  x.drawImage(img,0,0,W,H);
  const d=x.getImageData(0,0,W,H).data;
  let r=0,g=0,b=0,n=0;
  for(let j=Math.floor(H*0.2);j<H*0.8;j++){
    for(let i=Math.floor(W*0.2);i<W*0.8;i++){
      const k=(j*W+i)*4, R=d[k],G=d[k+1],B=d[k+2],A=d[k+3];
      if(A<128) continue;
      // buang piksel terlalu putih (background) agar tidak bias
      if(R>235&&G>235&&B>235) continue;
      r+=R;g+=G;b+=B;n++;
    }
  }
  if(!n) return '#888888';
  return rgbToHex(r/n,g/n,b/n);
}
