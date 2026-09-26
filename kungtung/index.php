<?php
session_start();

include("config/database.php");

// Fetch active categories for filters
$categoriesData = [];
$catResult = mysqli_query($conn, "SELECT * FROM categories WHERE status='Active' ORDER BY name ASC");
while ($cat = mysqli_fetch_assoc($catResult)) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $cat['name']), '-'));
    $categoriesData[] = [
        'slug'  => $slug,
        'label' => $cat['name']
    ];
}

// Fetch active products (joined with category name) for the shop grid
$productsData = [];
$prodQuery = "SELECT
                p.*,
                c.name AS category_name,
                MIN(v.price) AS min_price,
                SUM(v.stock) AS total_stock,
                COUNT(v.id) AS total_variants
            FROM products p
            LEFT JOIN categories c
                ON p.category_id=c.id
            LEFT JOIN product_variants v
                ON p.id=v.product_id
            WHERE p.status='Active'
            GROUP BY p.id
            ORDER BY p.id DESC";
$prodResult = mysqli_query($conn, $prodQuery);

$i = 0;
while ($row = mysqli_fetch_assoc($prodResult)) {
    $catName = !empty($row['category_name']) ? $row['category_name'] : 'Uncategorized';
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $catName), '-'));
    $image = !empty($row['image']) ? $row['image'] : 'default.png';

    $variants = [];

$vQuery = mysqli_query($conn,"
SELECT *
FROM product_variants
WHERE product_id=".$row['id']."
ORDER BY price ASC
");

while($v=mysqli_fetch_assoc($vQuery))
{

    $variants[] = [

        'id' => (int)$v['id'],

        'volume' => $v['volume'],

        'price' => (float)$v['price'],

        'stock' => (int)$v['stock']

    ];

}

$productsData[] = [

    'id' => (int)$row['id'],

    'name' => $row['name'],

    'type' => $slug,

    'typeLabel' => $catName,

    'brand' => $row['brand'],

    'abv' => $row['abv'],

    'price' => (float)$row['min_price'],

    'stock' => (int)$row['total_stock'],

    'variants' => $variants,

    'desc' => $row['description'],

    'img' => 'uploads/products/'.$image,

    'featured' => $i < 3

];
    $i++;
}

// Load this user's saved cart from the database (empty for guests)
$cartData = [];

if(isset($_SESSION['user_id']))
{

    $cStmt = mysqli_prepare($conn,
    "SELECT
        product_id,
        variant_id,
        quantity
    FROM cart_items
    WHERE user_id=?");

    mysqli_stmt_bind_param($cStmt,"i",$_SESSION['user_id']);

    mysqli_stmt_execute($cStmt);

    $cRes = mysqli_stmt_get_result($cStmt);

    while($row=mysqli_fetch_assoc($cRes))
    {

        $cartData[] = [

            'id'        => (int)$row['product_id'],

            'variantId' => (int)$row['variant_id'],

            'qty'       => (int)$row['quantity']

        ];

    }

}

// Pull the logged-in user's saved details to pre-fill the checkout form
$userProfile = ['full_name' => '', 'phone_number' => '', 'address' => '', 'email' => ''];
if (isset($_SESSION['user_id'])) {
    $uStmt = mysqli_prepare($conn, "SELECT full_name, phone_number, address, email FROM users WHERE id=?");
    mysqli_stmt_bind_param($uStmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($uStmt);
    $uRes = mysqli_stmt_get_result($uStmt);
    if ($u = mysqli_fetch_assoc($uRes)) {
        $userProfile = $u;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Kung Tung Liquor Shop — Wine, Spirits & Beer</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#180C12;
  --bg-panel:#241119;
  --bg-panel-2:#2C1420;
  --wine:#6B1029;
  --wine-light:#9B2242;
  --wine-glow:rgba(155,34,66,.35);
  --gold:#C9A961;
  --gold-bright:#E4C888;
  --cream:#F3E9DD;
  --muted:#AE96A0;
  --sage:#8A9678;
  --line:rgba(201,169,97,.18);
  --shadow: 0 30px 60px -20px rgba(0,0,0,.6);
  --ease: cubic-bezier(.22,1,.36,1);
}
*{box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{
  margin:0;
  background:var(--bg);
  color:var(--cream);
  font-family:'Sora',sans-serif;
  font-weight:400;
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;
}
h1,h2,h3,h4{
  font-family:'Fraunces',serif;
  font-weight:500;
  margin:0;
  letter-spacing:-.01em;
}
p{margin:0;line-height:1.65;}
a{color:inherit;text-decoration:none;}
button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit;}
::selection{background:var(--wine-light);color:var(--cream);}

/* ===== PRELOADER CURTAIN ===== */
#curtain{
  position:fixed;inset:0;z-index:10000;
  display:flex;align-items:center;justify-content:center;
  background:var(--bg);
}
#curtain::before, #curtain::after{
  content:"";position:absolute;top:0;bottom:0;width:51%;
  background:linear-gradient(160deg, var(--bg-panel-2), var(--bg));
  transition:transform 1s var(--ease);
}
#curtain::before{left:0;transform-origin:left;}
#curtain::after{right:0;transform-origin:right;}
#curtain.open::before{transform:translateX(-100%);}
#curtain.open::after{transform:translateX(100%);}
#curtain .curtain-mark{
  position:relative;z-index:2;
  font-family:'Fraunces',serif;font-style:italic;font-size:26px;color:var(--gold);
  opacity:1;transition:opacity .4s;
  display:flex;align-items:center;gap:14px;
}
#curtain .curtain-mark .drop{
  width:8px;height:8px;border-radius:50% 50% 50% 0;background:var(--gold);
  transform:rotate(45deg);animation:dropPulse 1.1s ease-in-out infinite;
}
@keyframes dropPulse{0%,100%{opacity:.4;} 50%{opacity:1;}}
#curtain.hide{display:none;}

/* ===== SCROLL PROGRESS ===== */
#scrollProgress{
  position:fixed;top:0;left:0;height:2px;
  background:linear-gradient(90deg,var(--wine-light),var(--gold));
  width:0%;z-index:600;transition:width .12s linear;
}

/* ===== CUSTOM CURSOR (desktop only) ===== */
@media(hover:hover) and (pointer:fine){
  #cursorDot{
    position:fixed;top:0;left:0;width:8px;height:8px;border-radius:50%;
    background:var(--gold);pointer-events:none;z-index:9999;
    transform:translate(-50%,-50%);transition:width .25s var(--ease), height .25s var(--ease), opacity .25s;
    mix-blend-mode:difference;
  }
  #cursorRing{
    position:fixed;top:0;left:0;width:34px;height:34px;border-radius:50%;
    border:1px solid var(--gold);pointer-events:none;z-index:9999;
    transform:translate(-50%,-50%);transition:width .3s var(--ease), height .3s var(--ease), transform .12s linear, opacity .3s, border-color .3s;
    opacity:.6;
  }
  #cursorRing.big{width:64px;height:64px;border-color:var(--cream);}
  body{cursor:none;}
  a, button, [data-nav], [data-add]{cursor:none;}
}

/* film grain texture */
.grain{
  position:fixed;inset:0;pointer-events:none;z-index:9998;opacity:.05;mix-blend-mode:overlay;
}
.grain svg{width:100%;height:100%;}

.vignette{
  position:fixed;inset:0;pointer-events:none;z-index:1;
  background:radial-gradient(120% 90% at 50% -10%, rgba(155,34,66,.14), transparent 60%),
             radial-gradient(140% 100% at 50% 110%, rgba(0,0,0,.55), transparent 55%);
}

.eyebrow{
  font-family:'JetBrains Mono',monospace;
  font-size:11.5px;
  letter-spacing:.22em;
  text-transform:uppercase;
  color:var(--gold);
  display:inline-flex;
  align-items:center;
  gap:10px;
}
.eyebrow::before{
  content:"";
  width:22px;height:1px;
  background:var(--gold);
  display:inline-block;
}

/* ===== NAV ===== */
header{
  position:fixed;top:0;left:0;right:0;z-index:500;
  display:flex;align-items:center;justify-content:space-between;
  padding:22px 5vw;
  backdrop-filter:blur(14px);
  background:linear-gradient(to bottom, rgba(24,12,18,.85), rgba(24,12,18,0));
  transition:padding .4s var(--ease), background .4s var(--ease);
  flex-wrap:nowrap;
  gap:10px;
}
header.scrolled{
  padding:14px 5vw;
  background:rgba(20,10,15,.92);
  border-bottom:1px solid var(--line);
}
.logo{
  font-family:'Fraunces',serif;
  font-size:22px;
  font-weight:600;
  letter-spacing:.02em;
  display:flex;align-items:baseline;gap:6px;
  cursor:pointer;
  min-width:0;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
  flex-shrink:1;
}
.logo .amp{color:var(--gold);font-style:italic;font-weight:400;}
nav.mainnav{
  display:flex;gap:38px;align-items:center;
}

.login-btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 8px;
    background: #222;
    color: white;
    text-decoration: none;
}
.navlink{
  font-size:13px;
  letter-spacing:.06em;
  text-transform:uppercase;
  font-weight:500;
  color:var(--muted);
  position:relative;
  padding:6px 0;
  transition:color .3s;
}
.navlink::after{
  content:"";
  position:absolute;left:0;bottom:0;
  width:100%;height:2px;
  background:linear-gradient(90deg,var(--gold),var(--wine-light));
  transform:scaleX(0);
  transform-origin:left;
  transition:transform .45s var(--ease);
}
.navlink:hover, .navlink.active{color:var(--cream);}
.navlink:hover::after, .navlink.active::after{transform:scaleX(1);}

.nav-right{display:flex;align-items:center;gap:22px;flex-shrink:0;min-width:0;}
.cart-btn{
  position:relative;
  display:flex;align-items:center;gap:10px;
  border:1px solid var(--line);
  padding:9px 16px 9px 14px;
  border-radius:100px;
  font-size:12px;letter-spacing:.06em;text-transform:uppercase;
  transition:border-color .3s, background .3s;
  white-space:nowrap;
}
.cart-btn:hover{border-color:var(--gold);background:rgba(201,169,97,.08);}
.cart-badge{
  background:var(--gold);
  color:#1c0e14;
  font-family:'JetBrains Mono',monospace;
  font-weight:600;
  font-size:11px;
  min-width:18px;height:18px;
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  transform:scale(1);
  transition:transform .35s var(--ease);
}
.cart-badge.pour{animation:pourPop .55s var(--ease);}
@keyframes pourPop{
  0%{transform:scale(1);}
  35%{transform:scale(1.6);background:var(--gold-bright);}
  60%{transform:scale(.85);}
  100%{transform:scale(1);}
}
.burger{display:none;flex-shrink:0;}

/* ===== NAVBAR SEARCH ===== */
.nav-search{position:relative;flex-shrink:0;}
.nav-search-btn{
  width:40px;height:40px;border-radius:50%;
  border:1px solid var(--line);
  display:flex;align-items:center;justify-content:center;
  font-size:15px;color:var(--cream);
  transition:border-color .3s, background .3s;
}
.nav-search-btn:hover{border-color:var(--gold);background:rgba(201,169,97,.08);}
.nav-search.active .nav-search-btn{border-color:var(--gold);color:var(--gold-bright);}
.nav-search-backdrop{display:none;}
.nav-search-panel{
  position:absolute;top:52px;right:0;width:290px;
  background:#241119;border:1px solid rgba(201,169,97,.25);
  border-radius:14px;padding:14px;display:none;
  box-shadow:0 15px 30px rgba(0,0,0,.4);z-index:9999;
}
.nav-search.active .nav-search-panel{display:block;animation:popIn .25s var(--ease);}
.nsp-head{display:none;}
.nsp-close{
  width:30px;height:30px;border-radius:50%;flex-shrink:0;
  border:1px solid var(--line);color:var(--muted);font-size:13px;
  display:flex;align-items:center;justify-content:center;
  transition:color .3s, border-color .3s;
}
.nsp-close:hover{color:var(--gold-bright);border-color:var(--gold);}
.nav-search-panel form{
  display:flex;align-items:center;gap:8px;
  border:1px solid var(--line);border-radius:100px;
  padding:6px 6px 6px 16px;background:rgba(255,255,255,.03);
  transition:border-color .3s;
}
.nav-search-panel form:focus-within{border-color:var(--gold);}
.nav-search-panel input{
  flex:1;min-width:0;background:transparent;border:none;outline:none;
  color:var(--cream);font-family:'Sora',sans-serif;font-size:14px;
}
.nav-search-panel input::placeholder{color:var(--muted);}
.nav-search-panel button[type="submit"]{
  width:32px;height:32px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(120deg,var(--gold),var(--gold-bright));
  color:#1c0e14;font-size:13px;
  display:flex;align-items:center;justify-content:center;
  transition:transform .3s var(--ease);
}
.nav-search-panel button[type="submit"]:hover{transform:scale(1.08);}
.nav-search-hint{
  font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.06em;
  color:var(--muted);margin-top:10px;display:block;
}
.search-results{margin-top:12px;max-height:340px;overflow-y:auto;}
.search-results:empty{margin-top:0;}
.search-results .sr-group-label{
  font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.12em;text-transform:uppercase;
  color:var(--gold);margin:14px 0 8px;
}
.search-results .sr-group-label:first-child{margin-top:0;}
.search-result-item{
  display:flex;align-items:center;gap:12px;width:100%;text-align:left;
  padding:9px 8px;border-radius:8px;transition:background .2s;
}
.search-result-item:hover{background:rgba(201,169,97,.1);}
.search-result-item .sr-icon{font-size:15px;flex-shrink:0;width:20px;text-align:center;}
.search-result-item .sr-text{min-width:0;flex:1;}
.search-result-item .sr-title{font-size:13.5px;color:var(--cream);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.search-result-item .sr-sub{font-size:11px;color:var(--muted);}
.search-no-results{font-size:12.5px;color:var(--muted);padding:10px 4px;}

/* Mobile: search moves into the hamburger (3-line) menu, icon hides */
.mobile-nav-search{display:none;}

/* Full-width, user-friendly search overlay on mobile */
@media(max-width:900px){
  .nav-search.active .nav-search-backdrop{
    display:block;position:fixed;inset:0;
    background:rgba(10,5,8,.6);backdrop-filter:blur(2px);
    z-index:9997;
  }
  .nav-search-panel{
    position:fixed;top:64px;left:0;right:0;width:100%;
    border-radius:0 0 20px 20px;
    padding:18px 5vw 24px;
    border-left:none;border-right:none;border-top:none;
    box-shadow:0 25px 45px -10px rgba(0,0,0,.55);
    z-index:9999;
  }
  .nsp-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
  .nav-search-panel form{padding:9px 9px 9px 20px;}
  .nav-search-panel input{font-size:16px;}
  .nav-search-panel button[type="submit"]{width:36px;height:36px;font-size:14px;}
  .nav-search-hint{margin-top:14px;font-size:11px;}
}

/* ===== VIEWS ===== */
main{position:relative;z-index:2;}
.view{
  display:none;
  min-height:100vh;
  opacity:0;
  transform:translateY(14px);
}
.view.active{display:block;}
.view.enter{
  animation:viewIn .6s var(--ease) forwards;
}
@keyframes viewIn{
  to{opacity:1;transform:translateY(0);}
}

/* ===== HOME / HERO ===== */
.hero{
  min-height:100vh;
  display:flex;align-items:center;
  padding:120px 5vw 60px;
  position:relative;
  gap:60px;
  overflow:hidden;
}
.hero-bg{
  position:absolute;inset:0;z-index:0;overflow:hidden;
}
.hero-bg img{
  width:100%;height:100%;object-fit:cover;
  filter:saturate(.65) brightness(.32) contrast(1.1);
  animation:kenBurns 22s ease-in-out infinite alternate;
  opacity:.65;
}
@keyframes kenBurns{
  0%{transform:scale(1.05) translate(0,0);}
  100%{transform:scale(1.18) translate(-1.5%,-1%);}
}
.hero-bg::after{
  content:"";position:absolute;inset:0;
  background:linear-gradient(100deg, var(--bg) 15%, rgba(24,12,18,.55) 45%, rgba(24,12,18,.15) 75%),
             linear-gradient(0deg, var(--bg), transparent 30%);
}
.hero-copy, .hero-visual{position:relative;z-index:1;}
.hero-copy > *{opacity:0;transform:translateY(24px);animation:heroIn .9s var(--ease) forwards;}
.hero-copy .eyebrow{animation-delay:.15s;}
.hero-copy h1{animation-delay:.3s;}
.hero-copy p.lede{animation-delay:.5s;}
.hero-copy > div{animation-delay:.65s;}
@keyframes heroIn{to{opacity:1;transform:translateY(0);}}
.hero-copy{flex:1;max-width:620px;}
.hero-copy h1{
  font-size:clamp(48px, 6.4vw, 92px);
  line-height:.98;
  margin:22px 0 26px;
}
.hero-copy h1 .ital{font-style:italic;font-weight:400;color:var(--gold-bright);}
.hero-copy p.lede{
  font-size:17px;color:var(--muted);max-width:440px;margin-bottom:36px;
}

/* ===== SEARCH BAR (shop page) ===== */
.shop-search-bar{
  display:flex;align-items:center;gap:12px;
  border:1px solid var(--line);border-radius:100px;
  padding:13px 22px;max-width:440px;margin-bottom:28px;
  background:rgba(255,255,255,.03);
  transition:border-color .3s, background .3s;
}
.shop-search-bar:focus-within{border-color:var(--gold);background:rgba(255,255,255,.05);}
.shop-search-bar .ssb-icon{font-size:14px;color:var(--muted);flex-shrink:0;}
.shop-search-bar input{
  flex:1;background:transparent;border:none;outline:none;
  color:var(--cream);font-family:'Sora',sans-serif;font-size:14.5px;
}
.shop-search-bar input::placeholder{color:var(--muted);}
.shop-search-bar .ssb-clear{
  color:var(--muted);font-size:15px;flex-shrink:0;padding:2px 4px;
  transition:color .3s;display:none;
}
.shop-search-bar.has-value .ssb-clear{display:block;}
.shop-search-bar .ssb-clear:hover{color:var(--gold);}

.no-results{
  text-align:center;color:var(--muted);padding:70px 0;font-size:14.5px;
}
.no-results .eyebrow{justify-content:center;margin-bottom:14px;}

.user-menu{
    position:relative;
    flex-shrink:1;
    min-width:0;
}

.user-dropdown{
    position:absolute;
    top:55px;
    right:0;
    width:220px;
    background:#241119;
    border:1px solid rgba(201,169,97,.25);
    border-radius:12px;
    overflow:hidden;
    display:none;
    box-shadow:0 15px 30px rgba(0,0,0,.4);
    z-index:9999;
}

.user-dropdown a{
    display:block;
    padding:14px 18px;
    color:#F3E9DD;
    text-decoration:none;
    transition:.3s;
}

.user-dropdown a:hover{
    background:#6B1029;
}

.user-menu.active .user-dropdown{
    display:block;
}

.btn{
  display:inline-flex;align-items:center;gap:10px;
  padding:16px 30px;
  border-radius:100px;
  font-size:13px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;
  position:relative;overflow:hidden;
  transition:transform .3s var(--ease), box-shadow .3s;
}
.btn-primary{
  background:linear-gradient(120deg,var(--gold),var(--gold-bright));
  color:#1c0e14;
}
.btn-primary:hover{transform:translateY(-3px);box-shadow:0 14px 30px -8px rgba(201,169,97,.5);}
.btn-primary span.arrow{transition:transform .35s var(--ease);}
.btn-primary:hover span.arrow{transform:translateX(5px);}
.btn-ghost{
  border:1px solid var(--line);color:var(--cream);
}
.btn-ghost:hover{border-color:var(--gold);color:var(--gold-bright);}

/* ===== BUY NOW BUTTON ===== */
.btn-buy-now{
  background:linear-gradient(120deg, #6B1029, #9B2242);
  color:#fff;
  border:1px solid rgba(155,34,66,.4);
}
.btn-buy-now:hover{transform:translateY(-3px);box-shadow:0 14px 30px -8px rgba(155,34,66,.5);}
.btn-buy-now span.arrow{transition:transform .35s var(--ease);}
.btn-buy-now:hover span.arrow{transform:translateX(5px);}

.hero-visual{
  flex:1;display:flex;justify-content:center;align-items:center;
  position:relative;min-height:520px;
}
.glass-wrap{position:relative;width:260px;}
.glass-glow{
  position:absolute;inset:-40px;
  background:radial-gradient(circle, var(--wine-glow), transparent 65%);
  filter:blur(10px);
  animation:glowPulse 4s ease-in-out infinite;
}
@keyframes glowPulse{
  0%,100%{opacity:.55;transform:scale(1);}
  50%{opacity:.9;transform:scale(1.08);}
}
.glass-svg{position:relative;width:100%;height:auto;display:block;}
#pourLevel{transform:translateY(155px);transition:none;}
.glass-wrap.filled #pourLevel{
  animation:fillGlass 1.6s var(--ease) forwards .3s;
}
@keyframes fillGlass{
  to{transform:translateY(38px);}
}
.bubble{
  fill:rgba(243,233,221,.55);
  opacity:0;
}
.glass-wrap.filled .bubble{
  animation:rise 2.6s ease-in infinite;
}
@keyframes rise{
  0%{opacity:0;transform:translateY(0);}
  15%{opacity:.8;}
  90%{opacity:0;}
  100%{transform:translateY(-140px);}
}

.marquee{
  border-top:1px solid var(--line);border-bottom:1px solid var(--line);
  padding:18px 0;overflow:hidden;white-space:nowrap;margin-top:20px;
}
.marquee-track{display:inline-flex;gap:60px;animation:scroll 26s linear infinite;}
.marquee span{
  font-family:'JetBrains Mono',monospace;font-size:12px;letter-spacing:.14em;
  text-transform:uppercase;color:var(--muted);
}
.marquee span em{color:var(--gold);font-style:normal;}
@keyframes scroll{
  from{transform:translateX(0);}
  to{transform:translateX(-50%);}
}

/* ===== SECTION SHELL ===== */
section.block{padding:110px 5vw;}
.block-head{
  display:flex;justify-content:space-between;align-items:flex-end;
  margin-bottom:56px;gap:30px;flex-wrap:wrap;
}
.block-head h2{font-size:clamp(30px,3.4vw,46px);margin-top:14px;max-width:520px;}
.block-head p{color:var(--muted);max-width:340px;font-size:14.5px;}

[data-reveal]{
  opacity:0;transform:translateY(28px);
  transition:opacity .8s var(--ease), transform .8s var(--ease);
}
[data-reveal].in-view{opacity:1;transform:translateY(0);}

/* ===== FEATURED / CARDS ===== */
.card-grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:28px;
}
.wine-card{
  background:linear-gradient(160deg, var(--bg-panel), var(--bg-panel-2));
  border:1px solid var(--line);
  border-radius:18px;
  padding:26px 24px 24px;
  position:relative;
  cursor:pointer;
  transform-style:preserve-3d;
  transition:border-color .35s, box-shadow .35s;
}
.wine-card:hover{border-color:rgba(201,169,97,.5);box-shadow:var(--shadow);}
.wine-card .glow{
  position:absolute;inset:0;border-radius:18px;pointer-events:none;
  background:radial-gradient(220px circle at var(--mx,50%) var(--my,50%), rgba(201,169,97,.14), transparent 60%);
  opacity:0;transition:opacity .4s;
}
.wine-card:hover .glow{opacity:1;}
.bottle-stage{
  height:230px;display:flex;align-items:center;justify-content:center;
  position:relative;margin-bottom:18px;overflow:hidden;border-radius:12px;
}
.bottle-photo-wrap{position:relative;width:100%;height:100%;overflow:hidden;border-radius:12px;}
.bottle-photo{
  width:100%;height:100%;object-fit:cover;display:block;
  filter:saturate(.8) brightness(.75) contrast(1.05);
  transition:transform .6s var(--ease), filter .5s;
  transform:scale(1.02);
}
.wine-card:hover .bottle-photo, .detail-stage:hover .bottle-photo{transform:scale(1.12) translateY(-4px);filter:saturate(1) brightness(.85) contrast(1.08);}
.bottle-photo-wrap::after{
  content:"";position:absolute;inset:0;
  background:linear-gradient(180deg, rgba(24,12,18,0) 40%, rgba(24,12,18,.85) 100%),
             linear-gradient(160deg, var(--wine-glow), transparent 55%);
  mix-blend-mode:multiply;pointer-events:none;
}
.bottle-photo-wrap::before{
  content:"";position:absolute;inset:0;z-index:2;
  background:linear-gradient(180deg, rgba(201,169,97,0), rgba(201,169,97,.06));
  pointer-events:none;
}
.type-red .bottle-photo{filter:saturate(.85) brightness(.7) contrast(1.1) hue-rotate(0deg);}
.type-white .bottle-photo{filter:saturate(.7) brightness(.85) contrast(1.05) hue-rotate(-5deg);}
.type-rose .bottle-photo{filter:saturate(.9) brightness(.8) contrast(1.05) hue-rotate(-8deg);}
.type-sparkling .bottle-photo{filter:saturate(.75) brightness(.85) contrast(1.05) hue-rotate(4deg);}
.type-orange .bottle-photo{filter:saturate(.9) brightness(.75) contrast(1.1) sepia(.25);}
.type-whiskey .bottle-photo{filter:saturate(.85) brightness(.75) contrast(1.12) sepia(.3);}
.type-gin .bottle-photo{filter:saturate(.6) brightness(.85) contrast(1.05) hue-rotate(-15deg);}
.type-rum .bottle-photo{filter:saturate(.9) brightness(.72) contrast(1.1) sepia(.2);}
.type-vodka .bottle-photo{filter:saturate(.4) brightness(.9) contrast(1.05);}
.type-tequila .bottle-photo{filter:saturate(.85) brightness(.78) contrast(1.1) hue-rotate(6deg);}
.type-beer .bottle-photo{filter:saturate(.75) brightness(.82) contrast(1.05) hue-rotate(2deg);}
.bottle-label{
  position:absolute;bottom:14px;left:50%;transform:translateX(-50%);
  width:auto;white-space:nowrap;text-align:center;z-index:3;
  font-family:'JetBrains Mono',monospace;
  font-size:9px;letter-spacing:.08em;color:var(--cream);
  background:rgba(20,10,15,.55);backdrop-filter:blur(4px);
  padding:5px 10px;border:1px solid rgba(243,233,221,.25);border-radius:4px;
  line-height:1.3;text-transform:uppercase;
}
.wine-tag{
  display:inline-block;font-family:'JetBrains Mono',monospace;font-size:10px;
  letter-spacing:.1em;text-transform:uppercase;color:var(--sage);
  border:1px solid rgba(138,150,120,.4);border-radius:100px;padding:4px 10px;margin-bottom:12px;
}
.wine-card h3{font-size:20px;margin-bottom:4px;}
.wine-meta{font-size:12.5px;color:var(--muted);margin-bottom:14px;}
.wine-foot{display:flex;justify-content:space-between;align-items:center;margin-top:10px;}
.price{font-family:'JetBrains Mono',monospace;font-size:16px;color:var(--gold-bright);}
.add-btn{
  width:38px;height:38px;border-radius:50%;
  border:1px solid var(--line);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;color:var(--cream);
  transition:background .3s, transform .3s, border-color .3s;
}
.add-btn:hover{background:var(--gold);color:#1c0e14;border-color:var(--gold);transform:rotate(90deg);}

/* ===== ABOUT TEASER (home) ===== */
.split{
  display:flex;gap:70px;align-items:center;
}
.split-visual{
  flex:0 0 40%;
  aspect-ratio:4/5;
  border-radius:20px;
  background:
    radial-gradient(120% 100% at 20% 0%, rgba(155,34,66,.35), transparent 60%),
    linear-gradient(160deg, var(--bg-panel-2), #150910);
  position:relative;overflow:hidden;
  display:flex;align-items:center;justify-content:center;
  clip-path:inset(0 0 0 0);
}
.about-photo{
  width:100%;height:100%;object-fit:cover;
  filter:saturate(.75) brightness(.6) contrast(1.1);
  animation:kenBurns 26s ease-in-out infinite alternate;
}
.split-visual::after{
  content:"";position:absolute;inset:0;
  background:linear-gradient(200deg, rgba(155,34,66,.35), transparent 55%),
             linear-gradient(0deg, rgba(24,12,18,.55), transparent 40%);
  pointer-events:none;
}
.split-text{flex:1;}
.split-text p{color:var(--muted);font-size:15.5px;margin:20px 0 28px;max-width:480px;}
.values{display:flex;gap:34px;margin-top:10px;flex-wrap:wrap;}
.value-item{max-width:180px;}
.value-item .vi-label{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--gold);letter-spacing:.14em;text-transform:uppercase;margin-bottom:8px;display:block;}
.value-item h4{font-size:16px;margin-bottom:6px;}
.value-item p{font-size:13px;color:var(--muted);}

/* ===== GALLERY (home) - ENHANCED FOR RESPONSIVENESS ===== */
.gallery-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  grid-auto-rows:200px;
  gap:18px;
}
.gallery-item{
  position:relative;
  border-radius:16px;
  overflow:hidden;
  border:1px solid var(--line);
  cursor:pointer;
}
.gallery-item.span-2{grid-column:span 2;grid-row:span 2;}
.gallery-item img{
  width:100%;height:100%;object-fit:cover;display:block;
  filter:saturate(.75) brightness(.72) contrast(1.08);
  transition:transform .7s var(--ease), filter .5s;
  transform:scale(1.02);
}
.gallery-item:hover img{transform:scale(1.1);filter:saturate(.95) brightness(.85) contrast(1.1);}
.gallery-item::after{
  content:"";position:absolute;inset:0;
  background:linear-gradient(180deg, rgba(24,12,18,0) 55%, rgba(24,12,18,.75) 100%);
  pointer-events:none;
}
.gallery-item .gi-label{
  position:absolute;left:16px;bottom:14px;z-index:2;
  font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.1em;
  text-transform:uppercase;color:var(--cream);
}

/* Gallery responsive improvements */
@media(max-width:1200px){
  .gallery-grid{grid-template-columns:repeat(3,1fr);grid-auto-rows:180px;}
  .gallery-item.span-2{grid-column:span 2;grid-row:span 1;}
}
@media(max-width:900px){
  .gallery-grid{grid-template-columns:repeat(2,1fr);grid-auto-rows:160px;gap:14px;}
  .gallery-item.span-2{grid-column:span 2;grid-row:span 1;}
}
@media(max-width:768px){
  .gallery-grid{grid-template-columns:repeat(2,1fr);grid-auto-rows:130px;gap:12px;}
  .gallery-item.span-2{grid-column:span 1;grid-row:span 1;}
  .gallery-item .gi-label{font-size:9px;left:12px;bottom:10px;}
}
@media(max-width:600px){
  .gallery-grid{grid-template-columns:1fr;grid-auto-rows:180px;gap:10px;}
  .gallery-item .gi-label{font-size:8.5px;left:10px;bottom:8px;}
}
@media(max-width:480px){
  .gallery-grid{grid-auto-rows:140px;gap:8px;}
  .gallery-item .gi-label{font-size:8px;}
}

/* ===== FULL GALLERY PAGE ===== */
.gallery-hero{padding:160px 5vw 60px;}
.gallery-hero h1{font-size:clamp(40px,5vw,68px);max-width:780px;}
.gallery-hero .ital{font-style:italic;color:var(--gold-bright);}
.gallery-hero p{color:var(--muted);font-size:15.5px;max-width:520px;margin-top:18px;}
.gallery-page-grid{padding:0 5vw 110px;}

@media(max-width:900px){
  .gallery-hero{padding:130px 4vw 40px;}
}
@media(max-width:600px){
  .gallery-hero{padding:110px 4vw 30px;}
  .gallery-hero h1{font-size:clamp(30px,6vw,44px);}
}

/* ===== FILTERS ===== */
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:44px;}
.filter-pill{
  padding:9px 18px;border-radius:100px;border:1px solid var(--line);
  font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);
  transition:all .3s var(--ease);
}
.filter-pill:hover{border-color:var(--gold);color:var(--cream);}
.filter-pill.active{background:var(--gold);border-color:var(--gold);color:#1c0e14;font-weight:600;}
.products-grid{
  display:grid;grid-template-columns:repeat(4,1fr);gap:24px;
}
@media(max-width:1180px){.products-grid{grid-template-columns:repeat(3,1fr);} .card-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.products-grid{grid-template-columns:repeat(2,1fr);gap:16px;}}
@media(max-width:600px){.products-grid{grid-template-columns:1fr;gap:14px;}}
.products-grid .wine-card{transition:opacity .4s, transform .4s;}
.products-grid .grid-item.hide{display:none;}

/* ===== PRODUCT DETAIL ===== */
.detail-wrap{
  display:flex;gap:80px;padding:150px 5vw 100px;align-items:flex-start;
}
.detail-stage{
  flex:0 0 40%;
  height:560px;border-radius:24px;
  background:radial-gradient(120% 100% at 30% 10%, rgba(155,34,66,.3), transparent 60%),
    linear-gradient(160deg, var(--bg-panel), #150910);
  display:flex;align-items:center;justify-content:center;
  position:sticky;top:130px;
}
.detail-stage{overflow:hidden;}
.detail-stage .bottle-photo-wrap{width:82%;height:88%;border-radius:18px;}
.detail-stage .bottle-photo{filter:saturate(.9) brightness(.8) contrast(1.08);}
.back-link{
  font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--muted);
  display:inline-flex;align-items:center;gap:8px;margin-bottom:30px;letter-spacing:.05em;
  transition:color .3s;
}
.back-link:hover{color:var(--gold);}
.detail-info{flex:1;max-width:560px;}
.detail-info h1{font-size:clamp(32px,3.6vw,50px);margin:14px 0 8px;}
.detail-info .region{color:var(--muted);font-size:15px;margin-bottom:24px;}
.detail-price{font-family:'JetBrains Mono',monospace;font-size:28px;color:var(--gold-bright);margin-bottom:30px;}
.tasting-notes{display:flex;gap:10px;flex-wrap:wrap;margin:20px 0 30px;}
.note-chip{
  padding:8px 14px;border-radius:100px;border:1px solid rgba(138,150,120,.4);
  font-size:12.5px;color:var(--sage);
}
.detail-desc{color:var(--muted);font-size:15px;line-height:1.75;margin-bottom:30px;white-space:pre-line;}
.detail-row{display:flex;gap:40px;padding:20px 0;border-top:1px solid var(--line);}
.detail-row:last-of-type{border-bottom:1px solid var(--line);}
.detail-row .dr-label{font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.1em;color:var(--gold);text-transform:uppercase;width:120px;flex-shrink:0;padding-top:2px;}
.detail-row .dr-val{font-size:14.5px;color:var(--cream);}
.qty-add{display:flex;align-items:center;gap:24px;margin-top:34px;flex-wrap:wrap;}
.stepper{display:flex;align-items:center;border:1px solid var(--line);border-radius:100px;overflow:hidden;}
.stepper button{width:42px;height:44px;font-size:18px;color:var(--cream);transition:background .3s;}
.stepper button:hover{background:rgba(201,169,97,.15);}
.stepper .qty-val{width:36px;text-align:center;font-family:'JetBrains Mono',monospace;font-size:15px;}

/* ===== CART VIEW ===== */
.cart-wrap{padding:150px 5vw 100px;max-width:920px;margin:0 auto;}
.cart-items{border-top:1px solid var(--line);}
.cart-row{
  display:flex;align-items:center;gap:24px;padding:24px 0;border-bottom:1px solid var(--line);
  animation:rowIn .5s var(--ease);
}
@keyframes rowIn{from{opacity:0;transform:translateX(-14px);} to{opacity:1;transform:translateX(0);}}
.cart-row .ci-mini{width:56px;height:74px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border-radius:8px;overflow:hidden;}
.cart-row .ci-mini .bottle-photo-wrap{border-radius:8px;}
.cart-row .ci-mini .bottle-label{display:none;}
.cart-row .ci-info{flex:1;min-width:0;}
.cart-row h4{font-size:16.5px;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cart-row .ci-meta{font-size:12.5px;color:var(--muted);}
.cart-row .stepper{flex-shrink:0;}
.cart-row .ci-price{font-family:'JetBrains Mono',monospace;color:var(--gold-bright);width:80px;text-align:right;flex-shrink:0;}
.remove-x{color:var(--muted);font-size:18px;padding:6px;flex-shrink:0;transition:color .3s;}
.remove-x:hover{color:var(--wine-light);}
.cart-summary{
  margin-top:36px;padding-top:24px;
  display:flex;flex-direction:column;gap:12px;align-items:flex-end;
}
.summary-line{display:flex;justify-content:space-between;width:280px;font-size:14px;color:var(--muted);}
.summary-line.total{color:var(--cream);font-size:18px;font-family:'JetBrains Mono',monospace;padding-top:12px;border-top:1px solid var(--line);width:280px;}
.empty-cart{text-align:center;padding:80px 0;color:var(--muted);}
.empty-cart .eyebrow{justify-content:center;margin-bottom:18px;}
.order-success{
  text-align:center;padding:100px 0;
}
.check-circle{
  width:78px;height:78px;border-radius:50%;border:2px solid var(--gold);
  display:flex;align-items:center;justify-content:center;margin:0 auto 26px;
  animation:popIn .5s var(--ease);
}
@keyframes popIn{from{transform:scale(0);opacity:0;} to{transform:scale(1);opacity:1;}}

/* ===== CHECKOUT ===== */
.checkout-layout{display:flex;gap:60px;align-items:flex-start;}
.checkout-form-col{flex:1;min-width:0;}
.checkout-summary-col{
  flex:0 0 340px;
  background:linear-gradient(160deg, var(--bg-panel), var(--bg-panel-2));
  border:1px solid var(--line);border-radius:18px;padding:26px;
  position:sticky;top:130px;
}
.checkout-summary-col h3{font-size:17px;margin-bottom:18px;}
.co-line{display:flex;justify-content:space-between;gap:10px;font-size:13.5px;color:var(--muted);padding:9px 0;border-bottom:1px solid var(--line);}
.co-line .co-name{color:var(--cream);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.co-totals{margin-top:14px;padding-top:6px;}
.co-totals .summary-line{width:100%;}
.co-totals .summary-line.total{width:100%;}
.payment-note{
  display:flex;align-items:flex-start;gap:12px;
  border:1px solid rgba(201,169,97,.35);border-radius:12px;
  padding:16px;margin:26px 0;background:rgba(201,169,97,.06);
}
.payment-note .pn-icon{font-size:20px;line-height:1;}
.payment-note h4{font-size:14.5px;margin-bottom:4px;}
.payment-note p{font-size:12.5px;color:var(--muted);}
.field-row{display:flex;gap:20px;}
.field-row .field{flex:1;min-width:0;}
.order-number{
  font-family:'JetBrains Mono',monospace;color:var(--gold-bright);font-size:15px;
  border:1px dashed var(--line);border-radius:10px;padding:12px 18px;display:inline-block;margin-bottom:22px;
  word-break:break-all;
}

/* ===== ABOUT VIEW - ENHANCED ===== */
.about-hero{padding:160px 5vw 80px;}
.about-hero h1{font-size:clamp(40px,5vw,68px);max-width:780px;}
.about-hero .ital{font-style:italic;color:var(--gold-bright);}
.founder-row{display:flex;gap:60px;padding:0 5vw 110px;align-items:flex-start;}
.founder-quote{flex:1;font-family:'Fraunces',serif;font-style:italic;font-size:26px;line-height:1.5;color:var(--cream);border-left:2px solid var(--gold);padding-left:30px;}
.founder-sig{flex:0 0 260px;color:var(--muted);font-size:14px;}
.founder-sig strong{display:block;color:var(--cream);font-family:'Fraunces',serif;font-size:17px;margin-bottom:6px;font-weight:500;}

/* About photo gallery section */
.about-gallery{
  padding:0 5vw 110px;
}
.about-gallery-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:24px;
}
.about-gallery-item{
  aspect-ratio:1;
  border-radius:16px;
  overflow:hidden;
  border:1px solid var(--line);
  position:relative;
}
.about-gallery-item img{
  width:100%;height:100%;object-fit:cover;
  filter:saturate(.75) brightness(.72) contrast(1.08);
  transition:transform .6s var(--ease), filter .4s;
}
.about-gallery-item:hover img{
  transform:scale(1.05);filter:saturate(.95) brightness(.85) contrast(1.1);
}
.about-gallery-item::after{
  content:"";position:absolute;inset:0;
  background:linear-gradient(180deg, rgba(24,12,18,0) 40%, rgba(24,12,18,.6) 100%);
  pointer-events:none;
}
.about-gallery-caption{
  position:absolute;bottom:0;left:0;right:0;
  padding:20px;color:var(--cream);font-size:13px;
  font-family:'Fraunces',serif;z-index:2;
}

@media(max-width:900px){
  .about-gallery-grid{grid-template-columns:repeat(2,1fr);gap:18px;}
}
@media(max-width:600px){
  .about-gallery-grid{grid-template-columns:1fr;gap:14px;}
}

.timeline-simple{padding:20px 5vw 110px;}
.tl-item{display:flex;gap:40px;padding:26px 0;border-top:1px solid var(--line);}
.tl-item:last-child{border-bottom:1px solid var(--line);}
.tl-year{font-family:'JetBrains Mono',monospace;color:var(--gold);font-size:14px;width:80px;flex-shrink:0;padding-top:2px;}
.tl-text h4{font-size:18px;margin-bottom:6px;}
.tl-text p{color:var(--muted);font-size:14px;max-width:520px;}

/* ===== CONTACT VIEW - ENHANCED RESPONSIVE ===== */
.contact-wrap{padding:160px 5vw 60px;display:flex;gap:80px;}
.contact-info{flex:0 0 340px;}
.contact-info h1{font-size:clamp(32px,3.6vw,46px);margin-bottom:20px;}
.contact-info p{color:var(--muted);font-size:15px;margin-bottom:34px;}
.ci-block{margin-bottom:26px;}
.ci-block .vi-label{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--gold);letter-spacing:.12em;text-transform:uppercase;display:block;margin-bottom:8px;}
.ci-block div.val{font-size:15px;}
.contact-badges{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
.cb-pill{
  font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.03em;
  color:var(--muted);border:1px solid var(--line);border-radius:100px;
  padding:8px 14px;white-space:nowrap;
}
.contact-form{flex:1;max-width:560px;}
.field{margin-bottom:22px;position:relative;}
.field label{
  font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);
  display:block;margin-bottom:8px;transition:color .3s;
}
.field input, .field textarea{
  width:100%;background:transparent;border:none;border-bottom:1px solid var(--line);
  color:var(--cream);font-family:'Sora',sans-serif;font-size:15.5px;padding:10px 2px;
  transition:border-color .3s;
}
.field input:focus, .field textarea:focus{outline:none;border-color:var(--gold);}
.field:focus-within label{color:var(--gold);}
.field textarea{resize:vertical;min-height:90px;}
.form-success{
  display:none;text-align:center;padding:50px 0;
}
.form-success.show{display:block;animation:popIn .5s var(--ease);}

/* ===== CONTACT MAP ===== */
.contact-map-wrap{padding:0 5vw 110px;}
.contact-map{
  border-radius:20px;
  overflow:hidden;
  border:1px solid var(--line);
  filter:saturate(.35) brightness(.85) contrast(1.05);
  transition:filter .4s var(--ease);
}
.contact-map:hover{filter:none;}
.contact-map iframe{display:block;width:100%;height:380px;border:0;}

/* ===== FOOTER - ENHANCED RESPONSIVE ===== */
footer{
  border-top:1px solid var(--line);
  padding:70px 5vw 34px;
  display:flex;justify-content:space-between;flex-wrap:wrap;gap:40px;
}
.footer-brand h3{font-size:24px;margin-bottom:10px;}
.footer-brand p{color:var(--muted);font-size:13.5px;max-width:260px;}
.footer-cols{display:flex;gap:70px;flex-wrap:wrap;}
.footer-col .vi-label{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--gold);letter-spacing:.12em;text-transform:uppercase;display:block;margin-bottom:14px;}
.footer-col a{display:block;color:var(--muted);font-size:13.5px;margin-bottom:10px;transition:color .3s;}
.footer-col a:hover{color:var(--cream);}
.footer-bottom{
  width:100%;border-top:1px solid var(--line);margin-top:40px;padding-top:24px;
  display:flex;justify-content:space-between;color:var(--muted);font-size:12px;font-family:'JetBrains Mono',monospace;flex-wrap:wrap;gap:10px;
}

/* ===== RESPONSIVE IMPROVEMENTS ===== */
@media(max-width:900px){
  header{padding:16px 4vw;}
  nav.mainnav{position:fixed;top:64px;left:0;right:0;background:rgba(20,10,15,.98);flex-direction:column;padding:26px 5vw;gap:22px;transform:translateY(-140%);opacity:0;transition:all .4s var(--ease);border-bottom:1px solid var(--line);max-height:calc(100vh - 64px);overflow-y:auto;}
  nav.mainnav.open{transform:translateY(0);opacity:1;}
  .burger{display:flex;flex-direction:column;gap:5px;width:24px;}
  .burger span{height:2px;background:var(--cream);border-radius:2px;}
  .nav-right{gap:10px;}
  .nav-search{display:none;}
  .mobile-nav-search{
    display:flex;align-items:center;gap:10px;order:-1;
    border:1px solid var(--line);border-radius:100px;
    padding:8px 8px 8px 20px;
    background:rgba(255,255,255,.03);
    transition:border-color .3s;
  }
  .mobile-nav-search:focus-within{border-color:var(--gold);}
  .mobile-nav-search input{
    flex:1;min-width:0;background:transparent;border:none;outline:none;
    color:var(--cream);font-family:'Sora',sans-serif;font-size:16px;
  }
  .mobile-nav-search input::placeholder{color:var(--muted);}
  .mobile-nav-search button{
    width:36px;height:36px;border-radius:50%;flex-shrink:0;
    background:linear-gradient(120deg,var(--gold),var(--gold-bright));
    color:#1c0e14;font-size:14px;
    display:flex;align-items:center;justify-content:center;
    transition:transform .3s var(--ease);
  }
  .mobile-nav-search button:hover{transform:scale(1.08);}
  .hero{flex-direction:column;padding-top:140px;}
  .hero-visual{order:-1;min-height:340px;}
  .hero-search{max-width:100%;}
  .glass-wrap{width:180px;}
  .split{flex-direction:column;}
  .split-visual{width:100%;flex:none;}
  .card-grid{grid-template-columns:1fr;}
  .products-grid{grid-template-columns:repeat(3,1fr);gap:14px;}
  .shop-search-bar{max-width:100%;}
  .detail-wrap{flex-direction:column;padding-top:120px;}
  .detail-stage{position:relative;top:0;width:100%;height:380px;}
  .founder-row{flex-direction:column;gap:30px;}
  .contact-wrap{flex-direction:column;gap:60px;}
  .checkout-layout{flex-direction:column;}
  .checkout-summary-col{position:relative;top:0;width:100%;flex:none;}
  .field-row{flex-direction:column;gap:0;}
  footer{padding:60px 5vw 30px;gap:30px;}
  .footer-cols{gap:50px;}
}

@media(max-width:768px){
  .products-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
  .shop-search-bar{max-width:100%;}
  .contact-info{flex:0 0 auto;}
  footer{padding:50px 5vw 25px;flex-direction:column;}
  .footer-brand{order:1;}
  .footer-cols{order:2;gap:40px;}
  .footer-bottom{order:3;flex-direction:column-reverse;gap:8px;}
}

@media(max-width:600px){
  .products-grid{grid-template-columns:repeat(3,1fr);gap:10px;}
  .products-grid .wine-card{padding:12px 10px 10px;border-radius:12px;}
  .products-grid .bottle-stage{height:110px;margin-bottom:10px;border-radius:8px;}
  .products-grid .bottle-label{display:none;}
  .products-grid .wine-tag{font-size:8px;padding:3px 7px;margin-bottom:6px;letter-spacing:.04em;}
  .products-grid .wine-card h3{font-size:12.5px;line-height:1.3;margin-bottom:2px;}
  .products-grid .wine-meta{font-size:9.5px;margin-bottom:8px;}
  .products-grid .wine-foot{margin-top:4px;}
  .products-grid .price{font-size:11.5px;}
  .products-grid .add-btn{width:26px;height:26px;font-size:14px;}
  .card-grid{grid-template-columns:1fr;}
  .hero{padding:100px 4vw 40px;}
  .hero-copy h1{font-size:clamp(36px,5vw,60px);}
  .marquee-track{gap:40px;}
  .block-head{flex-direction:column;gap:20px;}
  .block-head h2{font-size:clamp(24px,5vw,36px);}
  .block-head p{max-width:100%;}
  .split-text p{font-size:14px;}
  .values{gap:20px;}
  .value-item{max-width:100%;}
  .detail-info h1{font-size:clamp(24px,5vw,36px);}
  .detail-price{font-size:22px;}
  .detail-row{gap:20px;flex-direction:column;}
  .detail-row .dr-label{width:auto;}
  .qty-add{flex-direction:column;}
  .qty-add > *{width:100%;}
  .cart-row{gap:16px;flex-wrap:wrap;}
  .cart-row .ci-price{width:auto;}
  .summary-line, .summary-line.total{width:100%;}
  .about-hero{padding:120px 4vw 60px;}
  .about-hero h1{font-size:clamp(32px,5vw,48px);}
  .founder-row{gap:30px;}
  .founder-quote{font-size:20px;border-left:2px solid var(--gold);padding-left:20px;}
  .founder-sig{flex:none;}
  .timeline-simple{padding:20px 4vw 80px;}
  .tl-item{gap:20px;}
  .tl-year{width:60px;}
  .contact-wrap{padding:120px 4vw 60px;}
  .contact-info h1{font-size:clamp(28px,5vw,40px);}
  .contact-map iframe{height:300px;}
  footer{padding:40px 4vw 20px;}
  .footer-brand p{font-size:12px;}
  .footer-col a{font-size:12.5px;}
  .footer-bottom{font-size:11px;}
}

/* ===== HEADER OVERFLOW FIX (small / older Android phones) ===== */
@media(max-width:480px){
  header{padding:14px 3.5vw;gap:6px;}
  header.scrolled{padding:10px 3.5vw;}
  .logo{font-size:17px;max-width:38vw;}
  .nav-right{gap:6px;}
  .cart-btn{padding:8px 12px;font-size:11px;gap:6px;}
  .cart-btn > span:not(.cart-badge){display:none;}
  .cart-btn::after{content:"🛒";font-size:13px;}
  #userBtn{padding:8px 10px;}
  #userBtn::after{content:none;}
  .user-menu .cart-btn .uname{
    max-width:60px;overflow:hidden;text-overflow:ellipsis;
    white-space:nowrap;display:inline-block;vertical-align:middle;
  }
  .user-dropdown{width:180px;right:-8px;top:48px;}
  .nav-search-btn{width:34px;height:34px;font-size:13px;}
  .nav-search-panel{top:56px;padding:16px 4vw 20px;}
  nav.mainnav{top:56px;padding:20px 4vw;max-height:calc(100vh - 56px);}
  .burger{width:20px;gap:4px;}
  .cart-badge{min-width:16px;height:16px;font-size:10px;}
  .products-grid{gap:8px;}
  .products-grid .bottle-stage{height:95px;}
  .hero-copy h1{font-size:clamp(32px,6vw,48px);}
  .hero-copy p.lede{font-size:14px;}
  section.block{padding:80px 4vw;}
  .payment-note{font-size:13px;padding:12px;}
  .payment-note .pn-icon{font-size:18px;}
}

@media(max-width:380px){
  .logo{font-size:15px;max-width:34vw;}
  header{padding:12px 3vw;gap:4px;}
  .nav-right{gap:4px;}
  .cart-btn{padding:7px 9px;}
  .cart-btn::after{font-size:12px;}
  #userBtn{padding:7px 8px;}
  .user-menu .cart-btn .uname{max-width:44px;}
  .nav-search-btn{width:30px;height:30px;font-size:12px;}
  .stepper button{width:36px;height:38px;}
  .detail-price{font-size:20px;}
  .cart-row{gap:12px;flex-wrap:wrap;}
  .cart-row .ci-price{width:auto;}
  .about-gallery-grid{grid-template-columns:1fr;}
  footer{padding:30px 3vw 15px;}
}

@media(max-width:320px){
  .logo{font-size:13px;max-width:30vw;}
  header{padding:10px 2.5vw;}
  .cart-btn{padding:6px 8px;}
  .user-menu .cart-btn .uname{max-width:32px;}
  section.block{padding:60px 3vw;}
}

@media (prefers-reduced-motion: reduce){
  *{animation-duration:.01ms !important;animation-iteration-count:1 !important;transition-duration:.01ms !important;scroll-behavior:auto !important;}
}
.whatsapp-float {
    position: fixed !important;
    bottom: 25px !important;
    right: 25px !important;

    width: 60px;
    height: 60px;

    background-color: #25D366;

    border-radius: 50%;

    display: flex !important;
    align-items: center;
    justify-content: center;

    z-index: 999999 !important;

    box-shadow: 0 4px 15px rgba(0,0,0,0.3);

    text-decoration: none;

    transition: transform 0.3s ease;
}

.whatsapp-float:hover {
    transform: scale(1.1);
}

.whatsapp-float svg {
    width: 34px;
    height: 34px;
    display: block;
}
</style>
</head>
<body>

<div id="curtain"><div class="curtain-mark"><span class="drop"></span>KUNG TUNG</div></div>
<div id="scrollProgress"></div>
<div id="cursorDot"></div>
<div id="cursorRing"></div>

<div class="vignette"></div>
<div class="grain"><svg><filter id="n"><feTurbulence type="fractalNoise" baseFrequency="0.85" numOctaves="2" stitchTiles="stitch"/></filter><rect width="100%" height="100%" filter="url(#n)"/></svg></div>

<header id="siteHeader">
  <div class="logo" data-nav="home">KUNG <span class="amp">&amp;</span> TUNG</div>
  <nav class="mainnav" id="mainNav">
    <form class="mobile-nav-search" id="mobileNavSearchForm">
      <input type="text" id="mobileNavSearchInput" placeholder="Search whisky, rum, wine, beer..." autocomplete="off" inputmode="search">
      <button type="submit" aria-label="Search">🔍</button>
    </form>
    <a class="navlink" data-nav="home">Home</a>
    <a class="navlink" data-nav="products">Shop</a>
    <a class="navlink" data-nav="about">About Us</a>
    <a class="navlink" data-nav="contact">Contact Us</a>
  </nav>

  <div class="nav-right">

  <div class="nav-search" id="navSearch">
      <button class="nav-search-btn" id="navSearchBtn" type="button" aria-label="Search">🔍</button>
      <div class="nav-search-backdrop" id="navSearchBackdrop"></div>
      <div class="nav-search-panel" id="navSearchPanel">
          <div class="nsp-head">
              <span class="eyebrow" style="margin:0;">Search the shop</span>
              <button type="button" class="nsp-close" id="navSearchClose" aria-label="Close search">✕</button>
          </div>
          <form id="navSearchForm">
              <input type="text" id="navSearchInput" placeholder="Search whisky, rum, wine, beer..." autocomplete="off" inputmode="search">
              <button type="submit" aria-label="Go">→</button>
          </form>
          <div class="search-results" id="navSearchResults"></div>
          <span class="nav-search-hint">Search anything in the shop</span>ltw1
      </div>
  </div>

  <?php if(isset($_SESSION['user_id'])): ?>

      <div class="user-menu">

          <button class="cart-btn" id="userBtn">
              👤 <span class="uname"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span> ▼
          </button>

          <div class="user-dropdown" id="userDropdown">

              <a href="login/profile.php">👤 My Profile</a>

              <?php if($_SESSION['role'] == 'admin'): ?>
                  <a href="admin/index.php">⚙ Admin Dashboard</a>
              <?php endif; ?>

              <a href="login/orders.php">📋 My Orders</a>
              <a href="login/logout.php">🚪 Logout</a>

          </div>

      </div>

  <?php else: ?>

      <a href="login/login.php" class="login-btn">
          Login
      </a>

  <?php endif; ?>

      <button class="cart-btn" data-nav="cart">
          <span>Cart</span>
          <span class="cart-badge" id="cartBadge">0</span>
      </button>

      <div class="burger" id="burger">
          <span></span>
          <span></span>
          <span></span>
      </div>

  </div>

</header>

<main>

  <!-- HOME -->
  <section class="view" id="view-home">
    <div class="hero">
      <div class="hero-bg"><img src="https://images.unsplash.com/photo-1553361371-9b22f78e8b1d?auto=format&fit=crop&w=1800&q=70" alt=""></div>
      <div class="hero-copy">
        <span class="eyebrow">Every bottle, one shop</span>
        <h1>Wine, spirits<br>&amp; <span class="ital">everything</span> else.</h1>
        <p class="lede">Kung Tung Liquor Shop stocks a full cellar and a full bar — wine, whiskey, gin, rum, vodka, tequila, and beer, sourced by taste and poured with care.</p>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <button class="btn btn-primary" data-nav="products">Shop the Collection <span class="arrow">→</span></button>
          <button class="btn btn-ghost" data-nav="about">Our Story</button>
        </div>
      </div>
      <div class="hero-visual">
        <div class="glass-wrap" id="glassWrap">
          <div class="glass-glow"></div>
          <svg class="glass-svg" viewBox="0 0 200 400" fill="none">
            <clipPath id="glassClip">
              <path d="M60 40 L140 40 L128 220 C126 250 74 250 72 220 Z"/>
            </clipPath>
            <g clip-path="url(#glassClip)">
              <rect id="pourLevel" x="50" y="40" width="100" height="220" fill="url(#wineGrad)"></rect>
              <circle class="bubble" cx="85" cy="230" r="2.4"/>
              <circle class="bubble" cx="105" cy="240" r="1.8" style="animation-delay:.6s"/>
              <circle class="bubble" cx="118" cy="225" r="2.1" style="animation-delay:1.2s"/>
              <circle class="bubble" cx="95" cy="235" r="1.6" style="animation-delay:1.8s"/>
            </g>
            <linearGradient id="wineGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#9B2242"/>
              <stop offset="100%" stop-color="#4A0A1C"/>
            </linearGradient>
            <path d="M60 40 L140 40 L128 220 C126 250 74 250 72 220 Z" stroke="rgba(243,233,221,.55)" stroke-width="2"/>
            <line x1="100" y1="250" x2="100" y2="330" stroke="rgba(243,233,221,.55)" stroke-width="2"/>
            <ellipse cx="100" cy="332" rx="34" ry="7" stroke="rgba(243,233,221,.55)" stroke-width="2"/>
            <path d="M60 40 L52 26 L148 26 L140 40 Z" stroke="rgba(243,233,221,.35)" stroke-width="1.5"/>
          </svg>
        </div>
      </div>
      
    </div>

    <div class="marquee">
      <div class="marquee-track">
        <span>Wine <em>·</em> Whiskey <em>·</em> Gin <em>·</em> Rum <em>·</em> Vodka <em>·</em> Tequila <em>·</em> Beer</span>
        <span>Wine <em>·</em> Whiskey <em>·</em> Gin <em>·</em> Rum <em>·</em> Vodka <em>·</em> Tequila <em>·</em> Beer</span>
      </div>
    </div>

    <section class="block" data-reveal>
      <div class="block-head">
        <div>
          <span class="eyebrow">Featured Bottles</span>
          <h2>This week's picks, across the whole shelf.</h2>
        </div>
        <p>Three selections our team is pouring right now — pulled from a shop of forty-one active labels across every category.</p>
      </div>
      <div class="card-grid" id="featuredGrid"></div>
    </section>

    <section class="block" data-reveal>
      <div class="split">
        <div class="split-visual">
          <img class="about-photo" src="https://images.unsplash.com/photo-1694781558887-d84d9ba7603f?auto=format&fit=crop&w=1200&q=70" alt="Bottles lined up on the shelves at Kung Tung Liquor Shop">
        </div>
        <div class="split-text">
          <span class="eyebrow">The Shop</span>
          <h2 style="font-size:clamp(28px,3vw,40px);margin-top:14px;">Not just wine. Everything worth pouring.</h2>
          <p>Kung Tung Liquor Shop started as a single shelf in a Pokhara corner store. Today it's a full-range cellar and bar shop — wine, whiskey, gin, rum, vodka, tequila and beer — chosen for what's in the bottle, not how it photographs.</p>
          <div class="values">
            <div class="value-item">
              <span class="vi-label">Selection</span>
              <h4>Every category</h4>
              <p>From cellar-worthy reds to small-batch spirits and cold beer.</p>
            </div>
            <div class="value-item">
              <span class="vi-label">Provenance</span>
              <h4>Traced to the source</h4>
              <p>Every label lists where it's from and how it's made.</p>
            </div>
            <div class="value-item">
              <span class="vi-label">Pairing</span>
              <h4>Bottle to glass</h4>
              <p>Every bottle ships with a tasting note and a serve to try first.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="block" data-reveal style="padding-top:0;">
      <div class="block-head">
        <div>
          <span class="eyebrow">Gallery</span>
          <h2>A look around the shop floor.</h2>
        </div>
        <p>A few frames from inside Kung Tung — the shelves, the pours, and the everyday details of the shop. <a data-nav="gallery" style="color:var(--gold-bright);text-decoration:underline;">View full gallery →</a></p>
      </div>
      <div class="gallery-grid">
        <div class="gallery-item span-2" data-nav="gallery">
          <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=1000&q=70" alt="Shelves of bottles inside Kung Tung Liquor Shop" loading="lazy">
          <span class="gi-label">The Shelf</span>
        </div>
        <div class="gallery-item" data-nav="gallery">
          <img src="https://images.unsplash.com/photo-1470337458703-46ad1756a187?auto=format&fit=crop&w=700&q=70" alt="Wine bottles on display" loading="lazy">
          <span class="gi-label">Wine Corner</span>
        </div>
        <div class="gallery-item" data-nav="gallery">
          <img src="https://images.unsplash.com/photo-1569529465841-dfecdab7503b?auto=format&fit=crop&w=700&q=70" alt="Whiskey bottles on the shelf" loading="lazy">
          <span class="gi-label">Whiskey Row</span>
        </div>
        <div class="gallery-item" data-nav="gallery">
          <img src="https://images.unsplash.com/photo-1585975356853-5f88a8fed3cb?auto=format&fit=crop&w=700&q=70" alt="A poured glass of wine" loading="lazy">
          <span class="gi-label">The Pour</span>
        </div>
        <div class="gallery-item span-2" data-nav="gallery">
          <img src="https://images.unsplash.com/photo-1560512823-829485b8bf24?auto=format&fit=crop&w=1000&q=70" alt="Bar counter at Kung Tung" loading="lazy">
          <span class="gi-label">The Counter</span>
        </div>
      </div>
    </section>
  </section>

  <!-- PRODUCTS -->
  <section class="view" id="view-products">
    <div style="padding:150px 5vw 20px;">
      <span class="eyebrow">The Full Shop</span>
      <h1 style="font-size:clamp(34px,4vw,54px);margin:16px 0 40px;max-width:600px;">Shop All Bottles</h1>
      <div class="shop-search-bar" id="shopSearchBar">
        <span class="ssb-icon">🔍</span>
        <input type="text" id="shopSearchInput" placeholder="Search by name or brand..." autocomplete="off">
        <span class="ssb-clear" id="shopSearchClear" title="Clear search">✕</span>
      </div>
      <div class="filters" id="filters"></div>
    </div>
    <section class="block" style="padding-top:0;">
      <div class="products-grid" id="productsGrid"></div>
      <p class="no-results" id="noResults" style="display:none;">
        <span class="eyebrow">No matches</span><br><br>
        No bottles match your search. Try a different name, brand, or category.
      </p>
    </section>
  </section>

  <!-- PRODUCT DETAIL -->
  <section class="view" id="view-detail">
    <div class="detail-wrap" id="detailWrap"></div>
  </section>

  <!-- GALLERY (full page) -->
  <section class="view" id="view-gallery">
    <div class="gallery-hero">
      <span class="eyebrow">Gallery</span>
      <h1>Inside the <span class="ital">shop</span>, bottle by bottle.</h1>
      <p>A closer look at Kung Tung — the shelves, the counter, and the everyday moments of the shop floor.</p>
    </div>
    <div class="gallery-page-grid">
      <div class="gallery-grid" id="fullGalleryGrid">
        <div class="gallery-item span-2">
          <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=1200&q=70" alt="Shelves of bottles inside Kung Tung Liquor Shop" loading="lazy">
          <span class="gi-label">The Shelf</span>
        </div>
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1470337458703-46ad1756a187?auto=format&fit=crop&w=800&q=70" alt="Wine bottles on display" loading="lazy">
          <span class="gi-label">Wine Corner</span>
        </div>
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1569529465841-dfecdab7503b?auto=format&fit=crop&w=800&q=70" alt="Whiskey bottles on the shelf" loading="lazy">
          <span class="gi-label">Whiskey Row</span>
        </div>
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1585975356853-5f88a8fed3cb?auto=format&fit=crop&w=800&q=70" alt="A poured glass of wine" loading="lazy">
          <span class="gi-label">The Pour</span>
        </div>
        <div class="gallery-item span-2">
          <img src="https://images.unsplash.com/photo-1560512823-829485b8bf24?auto=format&fit=crop&w=1200&q=70" alt="Bar counter at Kung Tung" loading="lazy">
          <span class="gi-label">The Counter</span>
        </div>
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1608343795548-92c151315dfc?auto=format&fit=crop&w=800&q=70" alt="Premium bottles on the shelf" loading="lazy">
          <span class="gi-label">Premium Spirits</span>
        </div>
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1694781558887-d84d9ba7603f?auto=format&fit=crop&w=800&q=70" alt="Bottles lined up on the shelves" loading="lazy">
          <span class="gi-label">The Cellar</span>
        </div>
        <div class="gallery-item span-2">
          <img src="https://images.unsplash.com/photo-1553361371-9b22f78e8b1d?auto=format&fit=crop&w=1200&q=70" alt="Wine and spirits atmosphere" loading="lazy">
          <span class="gi-label">Evening Pour</span>
        </div>
      </div>
    </div>
  </section>

  <!-- CART -->
  <section class="view" id="view-cart">
    <div class="cart-wrap">
      <span class="eyebrow">Your Selection</span>
      <h1 style="font-size:clamp(32px,3.6vw,48px);margin:16px 0 10px;">Cart</h1>
      <!-- Bulk order contact info -->
      <div style="background:rgba(201,169,97,.08);border:1px solid rgba(201,169,97,.25);border-radius:12px;padding:16px 20px;margin-bottom:30px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <span style="font-size:24px;">📞</span>
        <div>
          <strong style="color:var(--gold);">Bulk Orders &amp; Wholesale</strong>
          <p style="color:var(--muted);font-size:14px;margin:4px 0 0;">For large orders, events, or wholesale pricing, call us directly at <strong style="color:var(--cream);">+977-9827156133</strong> or email <strong style="color:var(--cream);">kuntung.9345@gmail.com</strong></p>
        </div>
      </div>
      <div id="cartContent"></div>
    </div>
  </section>

  <!-- CHECKOUT -->
  <section class="view" id="view-checkout">
    <div class="cart-wrap">
      <span class="eyebrow">Delivery Details</span>
      <h1 style="font-size:clamp(32px,3.6vw,48px);margin:16px 0 30px;">Checkout</h1>
      <div id="checkoutContent"></div>
    </div>
  </section>

  <!-- ABOUT -->
  <section class="view" id="view-about">
    <div class="about-hero">
      <span class="eyebrow">About Kung Tung Liquor Shop</span>
      <h1>A shop built one <span class="ital">bottle</span> at a time.</h1>
    </div>
    <div class="founder-row">
      <p class="founder-quote">"तपाईंको रोजाइ, हाम्रो सेवा। Whatever you're pouring for — a wedding, a late-night craving, or just a Tuesday — we want Kung Tung to have it ready and delivered."</p>
      <div class="founder-sig">
        <strong>Roshan Hamal</strong>
        Proprietor, Kung Tung Liquor Shop — Pokhara-6, Lakeside, Street No. 16. Beer, wine, rum, vodka and soft drinks, plus ready-made sitans and chakna, all under one roof.
      </div>
    </div>

    <!-- NEW: Enhanced About Gallery Section -->
    <div class="about-gallery" data-reveal>
      <div class="block-head">
        <div>
          <span class="eyebrow">Behind the Shop</span>
          <h2>The moments that make Kung Tung.</h2>
        </div>
      </div>
      <div class="about-gallery-grid">
        <div class="about-gallery-item">
          <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=800&q=70" alt="Shop interior">
          <div class="about-gallery-caption">The Full Cellar</div>
        </div>
        <div class="about-gallery-item">
          <img src="https://images.unsplash.com/photo-1470337458703-46ad1756a187?auto=format&fit=crop&w=800&q=70" alt="Wine selection">
          <div class="about-gallery-caption">Curated Selection</div>
        </div>
        <div class="about-gallery-item">
          <img src="https://images.unsplash.com/photo-1569529465841-dfecdab7503b?auto=format&fit=crop&w=800&q=70" alt="Spirits section">
          <div class="about-gallery-caption">Spirits & Liqueurs</div>
        </div>
        <div class="about-gallery-item">
          <img src="https://images.unsplash.com/photo-1585975356853-5f88a8fed3cb?auto=format&fit=crop&w=800&q=70" alt="Tasting moment">
          <div class="about-gallery-caption">The Tasting</div>
        </div>
        <div class="about-gallery-item">
          <img src="https://images.unsplash.com/photo-1560512823-829485b8bf24?auto=format&fit=crop&w=800&q=70" alt="Bar counter">
          <div class="about-gallery-caption">Service Counter</div>
        </div>
        <div class="about-gallery-item">
          <img src="https://images.unsplash.com/photo-1608343795548-92c151315dfc?auto=format&fit=crop&w=800&q=70" alt="Premium bottles">
          <div class="about-gallery-caption">Premium Spirits</div>
        </div>
      </div>
    </div>

    <div class="timeline-simple" data-reveal>
      <div class="tl-item">
        <div class="tl-year">2018</div>
        <div class="tl-text"><h4>One shelf, one corner store</h4><p>Kung Tung opens as a six-bottle shelf inside a friend's shop, each label hand-numbered.</p></div>
      </div>
      <div class="tl-item">
        <div class="tl-year">2021</div>
        <div class="tl-text"><h4>The shop door</h4><p>A proper storefront opens, built around a full shelf of wine, spirits and beer.</p></div>
      </div>
      <div class="tl-item">
        <div class="tl-year">2024</div>
        <div class="tl-text"><h4>Direct-to-source buying</h4><p>We stop buying through unnecessary middlemen — every bottle now traces back to a source we know.</p></div>
      </div>
      <div class="tl-item">
        <div class="tl-year">Now</div>
        <div class="tl-text"><h4>Forty-one active labels</h4><p>A rotating shop of wine, whiskey, gin, rum, vodka, tequila and beer — rarely the same shelf twice.</p></div>
      </div>
    </div>
    <section class="block" data-reveal style="padding-top:0;">
      <div class="block-head">
        <div>
          <span class="eyebrow">Why Kung Tung</span>
          <h2>Stocked, served and delivered — Pokhara Valley wide.</h2>
        </div>
        <p>Everything the shop offers beyond the shelf, in one place.</p>
      </div>
      <div class="values">
        <div class="value-item">
          <span class="vi-label">Selection</span>
          <h4>Beer · Wine · Rum · Vodka</h4>
          <p>Plus soft drinks, and all ready-made items, sitans &amp; chakna on hand.</p>
        </div>
        <div class="value-item">
          <span class="vi-label">Delivery</span>
          <h4>All over Pokhara Valley</h4>
          <p>24-hour service with late-night delivery available whenever you need it.</p>
        </div>
        <div class="value-item">
          <span class="vi-label">Bulk &amp; Events</span>
          <h4>Weddings, hotels &amp; bars</h4>
          <p>Special service for wedding parties, hotels, bars, and private events — bulk orders available, best price guaranteed.</p>
        </div>
        <div class="value-item">
          <span class="vi-label">On Site</span>
          <h4>Easy parking</h4>
          <p>Pull right up to the shop door — parking is never a problem at Kung Tung.</p>
        </div>
      </div>
      <p style="color:var(--muted);font-size:12.5px;margin-top:34px;font-family:'JetBrains Mono',monospace;letter-spacing:.04em;">🔞 18+ Customers Only</p>
    </section>
  </section>

  <!-- CONTACT -->
  <section class="view" id="view-contact">
    <div class="contact-wrap">
      <div class="contact-info">
        <span class="eyebrow">Get in Touch</span>
        <h1>Contact Us</h1>
        <p>तपाईंको रोजाइ, हाम्रो सेवा। We're open 24 hours — reach out any time.</p>
        <div class="ci-block"><span class="vi-label">Visit</span><div class="val">Pokhara-6, Lakeside, Street No. 16</div></div>
        <div class="ci-block"><span class="vi-label">Call / WhatsApp</span><div class="val">9827156133</div></div>
        <div class="ci-block"><span class="vi-label">Email</span><div class="val">kuntung.9345@gmail.com</div></div>
        <div class="ci-block"><span class="vi-label">Hours</span><div class="val">Open 24 Hours &middot; Delivery across Pokhara Valley</div></div>
        <div class="contact-badges">
          <span class="cb-pill">🔞 18+ Only</span>
          <span class="cb-pill">🚗 Easy Parking</span>
          <span class="cb-pill">📸 Instagram &amp; TikTok</span>
        </div>
      </div>
      <div class="contact-form">
        <form id="contactForm">
          <div class="field"><label>Full name</label><input type="text" required placeholder="Your name"></div>
          <div class="field"><label>Email</label><input type="email" required placeholder="you@email.com"></div>
          <div class="field"><label>Message</label><textarea required placeholder="Tell us what you're looking for..."></textarea></div>
          <button type="submit" class="btn btn-primary">Send Message <span class="arrow">→</span></button>
        </form>
        <div class="form-success" id="formSuccess">
          <div class="check-circle">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M4 12L9 17L20 6" stroke="var(--gold)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <h3 style="font-size:22px;margin-bottom:8px;">Message received.</h3>
          <p style="color:var(--muted);">We'll uncork a reply soon.</p>
        </div>
      </div>
    </div>
   <div class="contact-map-wrap">
    <span class="eyebrow" style="margin-bottom:20px;display:inline-flex;">
        Find Us
    </span>

    <div class="contact-map">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d225016.32131423583!2d83.65490409453128!3d28.210957600000004!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39959500161fe7f3%3A0x27c49f3f5456776c!2sKuntung%20liquor%20shop!5e0!3m2!1sen!2snp!4v1787209718329!5m2!1sen!2snp" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>"
          >
        </iframe>
    </div>
</div>
    </div>
  </section>

</main>

<footer>
  <div class="footer-brand">
    <h3>KUNG TUNG</h3>
    <p>तपाईंको रोजाइ, हाम्रो सेवा। Beer, wine, rum, vodka &amp; soft drinks — open 24 hours, delivered across Pokhara Valley.</p>
  </div>
  <div class="footer-cols">
    <div class="footer-col">
      <span class="vi-label">Explore</span>
      <a data-nav="home">Home</a>
      <a data-nav="products">Shop</a>
      <a data-nav="about">About</a>
      <a data-nav="contact">Contact</a>
    </div>
    <div class="footer-col">
      <span class="vi-label">Shop Door</span>
      <a>Pokhara-6, Lakeside, Street No. 16</a>
      <a>Open 24 Hours</a>
      <a>9827156133</a>
      <a>kuntung.9345@gmail.com</a>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 Kung Tung Liquor Shop</span>
    <span>Please drink responsibly &middot; 18+ Only</span>
  </div>
</footer>

<svg style="display:none">
  <defs>
    <symbol id="bottleShape" viewBox="0 0 100 300">
      <path d="M40,0 L60,0 L60,46 C60,58 75,64 75,88 L75,268 C75,285 64,296 50,296 C36,296 25,285 25,268 L25,88 C25,64 40,58 40,46 Z" fill="currentColor"/>
      <rect x="38" y="0" width="24" height="14" fill="currentColor" opacity=".75"/>
    </symbol>
  </defs>
</svg>

<script>
/* ================= DATA (live from database) ================= */
const wines = <?php echo json_encode($productsData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

const productTypes = <?php echo json_encode(array_merge([['slug' => 'all', 'label' => 'All']], $categoriesData), JSON_UNESCAPED_UNICODE); ?>;

const typeColors = {red:"type-red", white:"type-white", rose:"type-rose", sparkling:"type-sparkling", orange:"type-orange", whiskey:"type-whiskey", whisky:"type-whiskey", gin:"type-gin", rum:"type-rum", vodka:"type-vodka", tequila:"type-tequila", beer:"type-beer"};

/* ================= SITE-WIDE SEARCH (products + static pages) ================= */
const sitePages = [
  { nav:'about', title:'About Us', keywords:'about us story history founder ishani rai timeline shop history direct source buying' },
  { nav:'contact', title:'Contact Us', keywords:'contact us email phone address visit hours location lakeside road pokhara call get in touch map' },
  { nav:'gallery', title:'Gallery', keywords:'gallery photos pictures shop floor images look around' },
  { nav:'home', title:'Home', keywords:'home wine spirits beer whiskey gin rum vodka tequila kung tung shop collection gallery' }
];

function scoreMatch(text, q){
  text = (text||'').toLowerCase(); q = (q||'').toLowerCase().trim();
  if(!q) return 0;
  if(text.includes(q)) return text.startsWith(q) ? 2 : 1;
  return 0;
}

function searchSite(query){
  const q = (query||'').trim();
  if(!q) return {pages:[], products:[]};
  const pages = sitePages
    .map(p=>({...p, score:Math.max(scoreMatch(p.title,q), scoreMatch(p.keywords,q))}))
    .filter(p=>p.score>0)
    .sort((a,b)=>b.score-a.score);
  const products = wines
    .map(w=>({...w, score:Math.max(scoreMatch(w.name,q), scoreMatch(w.brand,q), scoreMatch(w.typeLabel,q))}))
    .filter(w=>w.score>0)
    .sort((a,b)=>b.score-a.score)
    .slice(0,6);
  return {pages, products};
}

function renderSearchResults(container, query){
  if(!container) return;
  if(!query || !query.trim()){ container.innerHTML=''; return; }
  const {pages, products} = searchSite(query);
  if(pages.length===0 && products.length===0){
    container.innerHTML = `<div class="search-no-results">No matches for "${esc(query)}"</div>`;
    return;
  }
  let html = '';
  if(pages.length){
    html += '<div class="sr-group-label">Pages</div>';
    html += pages.map(p=>`<button type="button" class="search-result-item" data-goto-page="${p.nav}"><span class="sr-icon">📄</span><span class="sr-text"><div class="sr-title">${esc(p.title)}</div></span></button>`).join('');
  }
  if(products.length){
    html += '<div class="sr-group-label">Products</div>';
    html += products.map(w=>`<button type="button" class="search-result-item" data-goto-product="${w.id}"><span class="sr-icon">🍾</span><span class="sr-text"><div class="sr-title">${esc(w.name)}</div><div class="sr-sub">${esc(w.typeLabel)}${w.brand?' · '+esc(w.brand):''}</div></span></button>`).join('');
  }
  container.innerHTML = html;
}

/* ================= AUTH / CART STATE ================= */
const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
const userProfile = <?php echo json_encode($userProfile, JSON_UNESCAPED_UNICODE); ?>;
let cart = <?php echo json_encode($cartData); ?>;
let currentFilter = "all";
let currentSearch = "";

/* ================= HELPERS ================= */
function esc(str){
  return (str==null ? '' : String(str))
    .replace(/&/g,'&amp;')
    .replace(/"/g,'&quot;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;');
}

/* ================= SERVER SYNC HELPERS ================= */
function postCart(url, data){
  return fetch(url, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams(data).toString()
  })
  .then(r => r.json())
  .catch(err => {
    console.error('Cart sync failed:', err);
    return {success:false};
  });
}

/* ================= RENDER HELPERS ================= */
function bottlePhoto(w, showLabel=true){
  return `<div class="bottle-photo-wrap ${typeColors[w.type]||''}">
    <img class="bottle-photo" src="${w.img}" alt="${w.name}, ${w.typeLabel}" loading="lazy">
    ${showLabel ? `<div class="bottle-label">${w.name}${w.volume ? ' · '+w.volume : ''}</div>` : ''}
  </div>`;
}

function wineCard(w){
  const firstVariant = w.variants && w.variants.length > 0 ? w.variants[0] : null;
  const price = firstVariant ? firstVariant.price : w.price;
  const variantId = firstVariant ? firstVariant.id : 0;
  
  return `
  <div class="wine-card" data-id="${w.id}" data-nav="detail" data-tilt>
    <div class="glow"></div>
    <div class="bottle-stage">${bottlePhoto(w)}</div>
    <span class="wine-tag">${w.typeLabel}</span>
    <h3>${w.name}</h3>
    <div class="wine-meta">${w.brand||''}${w.brand && w.volume ? ' &middot; ' : ''}${w.volume||''}</div>
    <div class="wine-foot">
      <span class="price">Rs ${price}</span>
      <button class="add-btn" data-add="${w.id}" data-variant="${variantId}" title="Add to cart">+</button>
    </div>
  </div>`;
}

function renderFeatured(){
  document.getElementById('featuredGrid').innerHTML = wines.filter(w=>w.featured).map(wineCard).join('');
}

function renderFilters(){
  document.getElementById('filters').innerHTML = productTypes.map(t=>
    `<button class="filter-pill ${t.slug===currentFilter?'active':''}" data-filter="${t.slug}">${t.label}</button>`
  ).join('');
}

const userBtn = document.getElementById("userBtn");

if(userBtn){
    userBtn.addEventListener("click", function(e){
        e.stopPropagation();
        document.querySelector(".user-menu").classList.toggle("active");
    });
    document.addEventListener("click", function(){
        document.querySelector(".user-menu").classList.remove("active");
    });
}

/* ================= PRODUCT SEARCH + FILTER ================= */
function renderProducts(){
  document.getElementById('productsGrid').innerHTML = wines.map(w=>{
    const searchBlob = esc((w.name+' '+(w.brand||'')+' '+w.typeLabel).toLowerCase());
    return `<div class="grid-item" data-type="${w.type}" data-search="${searchBlob}">${wineCard(w)}</div>`;
  }).join('');
  attachTiltAll();
  attachCardNav();
  attachAddButtons();
  applyProductFilters();
}

function applyProductFilters(){
  const q = currentSearch.trim().toLowerCase();
  let visibleCount = 0;
  document.querySelectorAll('#productsGrid .grid-item').forEach(item=>{
    const typeOk = currentFilter==='all' || item.dataset.type===currentFilter;
    const searchOk = !q || item.dataset.search.includes(q);
    const show = typeOk && searchOk;
    item.classList.toggle('hide', !show);
    if(show) visibleCount++;
  });
  const noRes = document.getElementById('noResults');
  if(noRes) noRes.style.display = visibleCount===0 ? 'block' : 'none';
}

function renderDetail(id){

    const w = wines.find(x => x.id == id);

    if(!w) return;

    let selectedVariant = w.variants[0];

    document.getElementById("detailWrap").innerHTML = `

    <div class="detail-stage">

        ${bottlePhoto(w)}

    </div>

    <div class="detail-info">

        <a class="back-link" data-nav="products">

            &larr; Back to shop

        </a>

        <span class="eyebrow">

            ${w.typeLabel}

        </span>

        <h1>

            ${w.name}

        </h1>

        <div class="region">

            ${w.brand || ''}

        </div>

        <div class="detail-price">

            Rs <span id="variantPrice">

                ${selectedVariant.price}

            </span>

            <span style="font-size:13px;color:var(--muted);">

                / bottle

            </span>

        </div>

        <div class="tasting-notes">

            ${[
                w.abv ? w.abv + "% ABV" : "",
                w.brand
            ]
            .filter(Boolean)
            .map(n => `<span class="note-chip">${n}</span>`)
            .join("")}

        </div>

        <p class="detail-desc">

            ${w.desc || ""}

        </p>

        <div class="detail-row">

            <div class="dr-label">

                Brand

            </div>

            <div class="dr-val">

                ${w.brand || "—"}

            </div>

        </div>

        <div class="detail-row">

            <div class="dr-label">

                Bottle Size

            </div>

            <div class="dr-val">

                <select
                    id="variantSelect"
                    class="form-select">

                    ${w.variants.map(v => `

                        <option
                            value="${v.id}"
                            data-price="${v.price}"
                            data-stock="${v.stock}">

                            ${v.volume} - Rs ${v.price}

                        </option>

                    `).join("")}

                </select>

            </div>

        </div>

        <div class="detail-row">

            <div class="dr-label">

                Availability

            </div>

            <div
                class="dr-val"
                id="variantStock">

                ${selectedVariant.stock} in stock

            </div>

        </div>

        <div class="detail-row">

            <div class="dr-label">

                ABV

            </div>

            <div class="dr-val">

                ${w.abv ? w.abv + "%" : "—"}

            </div>

        </div>

        <div class="qty-add">

            <div
                class="stepper"
                id="detailStepper">

                <button data-step="-1">−</button>

                <span
                    class="qty-val"
                    id="detailQty">

                    1

                </span>

                <button data-step="1">+</button>

            </div>

            <button
                class="btn btn-primary"
                id="detailAdd"
                style="flex:1;">

                Add to Cart

                <span class="arrow">

                    →

                </span>

            </button>

            <button
                class="btn btn-buy-now"
                id="detailBuyNow"
                style="flex:1;">

                Buy Now

                <span class="arrow">

                    →

                </span>

            </button>

        </div>

    </div>

    `;

    let qty = 1;

    const qtyEl = document.getElementById("detailQty");

    document.getElementById("detailStepper").addEventListener("click",function(e){

        const btn = e.target.closest("button");

        if(!btn) return;

        qty = Math.max(1, qty + parseInt(btn.dataset.step));

        qtyEl.textContent = qty;

    });

    document.getElementById("variantSelect").addEventListener("change",function(){

        const option = this.options[this.selectedIndex];

        document.getElementById("variantPrice").innerText =
        option.dataset.price;

        document.getElementById("variantStock").innerText =
        option.dataset.stock + " in stock";

    });

    document.getElementById("detailAdd").addEventListener("click",function(){

        const variantId = parseInt(
            document.getElementById("variantSelect").value
        );

        addToCart(
            w.id,
            variantId,
            qty
        );

    });

    document.getElementById("detailBuyNow").addEventListener("click",function(){
        const variantId = parseInt(document.getElementById("variantSelect").value);
        const qty = parseInt(document.getElementById("detailQty").textContent);

        if(!isLoggedIn){
            window.location.href = "login/login.php";
            return;
        }

        // Clear existing cart and add just this item
        cart = [];
        // Use the enhanced addToCart with stock check
        addToCart(w.id, variantId, qty);
        
        // Redirect to checkout after a short delay to ensure cart is synced
        setTimeout(function(){
            showView("checkout");
        }, 300);
    });

}
function cartLineTotal(){

    return cart.reduce((sum,c)=>{

        const w = wines.find(x=>x.id==c.id);

        if(!w) return sum;

        const variant = w.variants.find(v=>v.id==c.variantId);

        if(!variant) return sum;

        return sum + (variant.price * c.qty);

    },0);

}
function renderCart(){

    const el = document.getElementById("cartContent");

    if(cart.length==0){

        el.innerHTML = `
        <div class="empty-cart">

            <span class="eyebrow">
                Empty Cart
            </span>

            <h3 style="font-size:24px;margin:18px 0 10px;">
                Your cart is empty.
            </h3>

            <p style="margin-bottom:28px;">
                Nothing poured yet — the shop is waiting.
            </p>

            <button
                class="btn btn-primary"
                data-nav="products">

                Browse the Shop

                <span class="arrow">→</span>

            </button>

        </div>`;

        attachCardNav();

        return;

    }

    const rows = cart.map(c=>{

        const w = wines.find(x=>x.id==c.id);

        if(!w) return "";

        const variant = w.variants.find(v=>v.id==c.variantId);

        if(!variant) return "";

        return `

        <div
            class="cart-row"
            data-cid="${c.id}"
            data-variant="${c.variantId}">

            <div class="ci-mini">

                ${bottlePhoto(w,false)}

            </div>

            <div class="ci-info">

                <h4>

                    ${w.name}

                </h4>

                <div class="ci-meta">

                    ${w.brand || ""}

                    &middot;

                    ${variant.volume}

                </div>

            </div>

            <div
                class="stepper"
                data-cid="${c.id}"
                data-variant="${c.variantId}">

                <button data-cstep="-1">

                    −

                </button>

                <span class="qty-val">

                    ${c.qty}

                </span>

                <button data-cstep="1">

                    +

                </button>

            </div>

            <div class="ci-price">

                Rs ${(variant.price * c.qty).toFixed(0)}

            </div>

            <button
                class="remove-x"
                data-remove="${c.id}"
                data-variant="${c.variantId}">

                ✕

            </button>

        </div>

        `;

    }).join("");

    const subtotal = cartLineTotal();

    const shipping = subtotal>0 ? 100 : 0;

    el.innerHTML = `

    <div class="cart-items">

        ${rows}

    </div>

    <div class="cart-summary">

        <div class="summary-line">

            <span>Subtotal</span>

            <span>Rs ${subtotal.toFixed(0)}</span>

        </div>

        <div class="summary-line">

            <span>Shipping</span>

            <span>Rs ${shipping.toFixed(0)}</span>

        </div>

        <div class="summary-line total">

            <span>Total</span>

            <span>Rs ${(subtotal+shipping).toFixed(0)}</span>

        </div>

        <button
            class="btn btn-primary"
            id="checkoutBtn"
            style="margin-top:14px;">

            Checkout

            <span class="arrow">

                →

            </span>

        </button>

    </div>

    `;

    el.querySelectorAll("[data-cstep]").forEach(btn=>{

        btn.addEventListener("click",()=>{

            const pid = parseInt(btn.closest(".stepper").dataset.cid);

            const vid = parseInt(btn.closest(".stepper").dataset.variant);

            const step = parseInt(btn.dataset.cstep);

            const item = cart.find(c=>

                c.id==pid &&

                c.variantId==vid

            );

            if(!item) return;

            item.qty = Math.max(1,item.qty+step);

            updateCartBadge(true);

            renderCart();

            postCart("cart/update.php",{

                product_id: pid,

                variant_id: vid,

                qty: item.qty

            });

        });

    });

    el.querySelectorAll("[data-remove]").forEach(btn=>{

        btn.addEventListener("click",()=>{

            const pid = parseInt(btn.dataset.remove);

            const vid = parseInt(btn.dataset.variant);

            cart = cart.filter(c=>

                !(c.id==pid && c.variantId==vid)

            );

            updateCartBadge(true);

            renderCart();

            postCart("cart/remove.php",{

                product_id: pid,

                variant_id: vid

            });

        });

    });

    document.getElementById("checkoutBtn").addEventListener("click",()=>{

        if(!isLoggedIn){

            window.location.href="login/login.php";

            return;

        }

        showView("checkout");

    });

}

/* ================= CHECKOUT ================= */
function renderCheckout(){
  const el = document.getElementById('checkoutContent');
  if(cart.length===0){
    showView('cart');
    return;
  }
  const rows = cart.map(c=>{
    const w = wines.find(x=>x.id===c.id);
    if(!w) return '';
    const variant = w.variants.find(v=>v.id===c.variantId);
    if(!variant) return '';
    return `<div class="co-line"><span class="co-name">${w.name} &times; ${c.qty}</span><span>Rs ${(variant.price*c.qty).toFixed(0)}</span></div>`;
  }).join('');
  const subtotal = cartLineTotal();
  const shipping = subtotal > 0 ? 100 : 0;
  const total = subtotal + shipping;

  el.innerHTML = `
    <div class="checkout-layout">
      <div class="checkout-form-col">
        <div class="payment-note">
          <span class="pn-icon">💵</span>
          <div>
            <h4>Cash on Delivery</h4>
            <p>Pay in cash when your order arrives. No online payment needed.</p>
          </div>
        </div>
        <form id="checkoutForm">
          <div class="field"><label>Full name</label><input type="text" id="coName" required value="${(userProfile.full_name||'').replace(/"/g,'&quot;')}" placeholder="Your name"></div>
          <div class="field-row">
            <div class="field"><label>Phone number</label><input type="tel" id="coPhone" required value="${(userProfile.phone_number||'').replace(/"/g,'&quot;')}" placeholder="98XXXXXXXX"></div>
            <div class="field"><label>Email</label><input type="email" id="coEmail" value="${(userProfile.email||'').replace(/"/g,'&quot;')}" placeholder="you@email.com"></div>
          </div>
          <div class="field"><label>Delivery address</label><textarea id="coAddress" required placeholder="Street, area, landmark">${(userProfile.address||'')}</textarea></div>
          <div class="field"><label>City</label><input type="text" id="coCity" required value="Pokhara" placeholder="City"></div>
          <div class="field"><label>Order notes (optional)</label><textarea id="coNotes" placeholder="Delivery instructions, preferred time, etc."></textarea></div>
          <button type="submit" class="btn btn-primary" id="placeOrderBtn">Place Order &mdash; Cash on Delivery <span class="arrow">→</span></button>
        </form>
      </div>
      <div class="checkout-summary-col">
        <h3>Order Summary</h3>
        ${rows}
        <div class="co-totals">
          <div class="summary-line"><span>Subtotal</span><span>Rs ${subtotal.toFixed(0)}</span></div>
          <div class="summary-line"><span>Shipping</span><span>Rs ${shipping.toFixed(0)}</span></div>
          <div class="summary-line total"><span>Total</span><span>Rs ${total.toFixed(0)}</span></div>
        </div>
      </div>
    </div>
  `;

  // ENHANCED: Checkout form submission with proper stock error handling
  document.getElementById('checkoutForm').addEventListener('submit', function(e){
    e.preventDefault();
    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.textContent = 'Placing order...';

    const payload = {
      full_name: document.getElementById('coName').value.trim(),
      phone: document.getElementById('coPhone').value.trim(),
      email: document.getElementById('coEmail').value.trim(),
      address: document.getElementById('coAddress').value.trim(),
      city: document.getElementById('coCity').value.trim(),
      notes: document.getElementById('coNotes').value.trim()
    };

    postCart('checkout/place_order.php', payload).then(res => {
      if(res.success){
        cart = [];
        updateCartBadge(true);
        renderOrderSuccess(res.order_number, res.total);
      } else {
        btn.disabled = false;
        btn.innerHTML = 'Place Order &mdash; Cash on Delivery <span class="arrow">→</span>';
        
        // ENHANCED: Detailed stock error handling
        if(res.error === 'out_of_stock') {
          let errorMsg = '❌ Stock Issues Detected!\n\n';
          
          if(res.error_details && res.error_details.length > 0) {
            errorMsg += 'The following items have insufficient stock:\n\n';
            res.error_details.forEach(err => {
              errorMsg += '• ' + err + '\n';
            });
            errorMsg += '\nPlease adjust your quantities and try again.';
          } else if(res.message) {
            errorMsg += res.message;
          } else {
            errorMsg += 'Some items in your cart are out of stock. Please remove or reduce quantities.';
          }
          
          alert(errorMsg);
          
        } else if(res.error === 'empty_cart') {
          alert('Your cart is empty.');
          showView('cart');
        } else if(res.error === 'missing_fields') {
          alert('Please fill in all required fields.');
        } else if(res.error === 'login_required') {
          alert('Please login to place an order.');
          window.location.href = 'login/login.php';
        } else if(res.error === 'db_error') {
          alert('A database error occurred. Please try again later.');
          console.error('DB Error:', res.message);
        } else {
          alert(res.message || res.error || 'Something went wrong. Please try again.');
        }
      }
    }).catch(err => {
      btn.disabled = false;
      btn.innerHTML = 'Place Order &mdash; Cash on Delivery <span class="arrow">→</span>';
      alert('Network error. Please check your connection and try again.');
      console.error('Checkout error:', err);
    });
  });
}

function renderOrderSuccess(orderNumber, total){
  const el = document.getElementById('checkoutContent');
  el.innerHTML = `
    <div class="order-success">
      <div class="check-circle"><svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M4 12L9 17L20 6" stroke="var(--gold)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <h3 style="font-size:26px;margin-bottom:14px;">Order placed.</h3>
      <div class="order-number">Order #${orderNumber}</div>
      <p style="color:var(--muted);max-width:420px;margin:0 auto 10px;">Pay Rs ${Number(total).toFixed(0)} in cash when it arrives. Your bottles are being packed with care.</p>
      <p style="color:var(--muted);max-width:420px;margin:0 auto 28px;font-size:13px;">A confirmation will follow shortly.</p>
      <button class="btn btn-ghost" data-nav="products">Continue Browsing</button>
    </div>`;
}

/* ================= CART LOGIC ================= */
// ENHANCED: addToCart with stock checking
function addToCart(productId, variantId, qty = 1){
    if(!isLoggedIn){
        window.location.href = "login/login.php";
        return;
    }

    // Check stock availability first
    fetch('cart/check_stock.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            product_id: productId,
            variant_id: variantId,
            qty: qty
        }).toString()
    })
    .then(r => r.json())
    .then(stockCheck => {
        if(!stockCheck.success){
            alert('⚠️ ' + stockCheck.error);
            return;
        }

        // Check if adding more would exceed stock
        const existing = cart.find(c => c.id == productId && c.variantId == variantId);
        const currentQty = existing ? existing.qty : 0;
        const newTotalQty = currentQty + qty;
        
        if(newTotalQty > stockCheck.stock) {
            alert('⚠️ Cannot add more. Only ' + stockCheck.stock + ' units available in stock. You already have ' + currentQty + ' in cart.');
            return;
        }

        if(existing){
            existing.qty = newTotalQty;
        } else {
            cart.push({
                id: productId,
                variantId: variantId,
                qty: qty
            });
        }

        updateCartBadge(true);

        postCart("cart/add.php",{
            product_id: productId,
            variant_id: variantId,
            qty: qty
        });
    })
    .catch(err => {
        console.error('Stock check failed:', err);
        // Fallback - try to add anyway
        const existing = cart.find(c => c.id == productId && c.variantId == variantId);
        if(existing){
            existing.qty += qty;
        } else {
            cart.push({
                id: productId,
                variantId: variantId,
                qty: qty
            });
        }
        updateCartBadge(true);
        postCart("cart/add.php",{
            product_id: productId,
            variant_id: variantId,
            qty: qty
        });
    });
}

function cartCount(){ return cart.reduce((s,c)=>s+c.qty,0); }
function updateCartBadge(animate){
  const badge = document.getElementById('cartBadge');
  badge.textContent = cartCount();
  if(animate){
    badge.classList.remove('pour');
    void badge.offsetWidth;
    badge.classList.add('pour');
  }
}

/* ================= NAVIGATION ================= */
function showView(name, opts={}){
  document.querySelectorAll('.view').forEach(v=>{v.classList.remove('active','enter');});
  const target = document.getElementById('view-'+name);
  target.classList.add('active');
  requestAnimationFrame(()=>target.classList.add('enter'));
  document.querySelectorAll('.navlink').forEach(l=>l.classList.toggle('active', l.dataset.nav===name));
  window.scrollTo({top:0, behavior:'instant' in document.documentElement.style ? 'instant':'auto'});
  document.getElementById('mainNav').classList.remove('open');
  if(name==='products') {
    renderFilters();
    renderProducts();
    const ssi = document.getElementById('shopSearchInput');
    if(ssi){
      ssi.value = currentSearch;
      document.getElementById('shopSearchBar').classList.toggle('has-value', !!currentSearch);
    }
  }
  if(name==='cart') renderCart();
  if(name==='checkout') renderCheckout();
  if(name==='detail' && opts.id) renderDetail(opts.id);
  refreshReveal();
}

document.addEventListener('click', e=>{
  const pageBtn = e.target.closest('[data-goto-page]');
  if(pageBtn){
    navSearch.classList.remove('active');
    const resEl = document.getElementById('navSearchResults');
    if(resEl) resEl.innerHTML = '';
    if(navSearchInput) navSearchInput.value = '';
    showView(pageBtn.dataset.gotoPage);
    return;
  }
  const prodBtn = e.target.closest('[data-goto-product]');
  if(prodBtn){
    navSearch.classList.remove('active');
    const resEl2 = document.getElementById('navSearchResults');
    if(resEl2) resEl2.innerHTML = '';
    if(navSearchInput) navSearchInput.value = '';
    showView('detail', {id: parseInt(prodBtn.dataset.gotoProduct)});
    return;
  }
  const navEl = e.target.closest('[data-nav]');
  if(navEl){
    e.preventDefault();
    const name = navEl.dataset.nav;
    if(name==='detail'){
      const card = navEl.closest('[data-id]');
      showView('detail', {id: parseInt(card.dataset.id)});
    } else {
      showView(name);
      const scrollTarget = navEl.dataset.scrollto;
      if(scrollTarget){
        setTimeout(()=>{
          const el = document.getElementById(scrollTarget);
          if(el) el.scrollIntoView({behavior:'smooth', block:'start'});
        }, 80);
      }
    }
    return;
  }
  const filterEl = e.target.closest('[data-filter]');
  if(filterEl){
    currentFilter = filterEl.dataset.filter;
    renderFilters();
    applyProductFilters();
    return;
  }
});

function attachAddButtons(){
  document.querySelectorAll('[data-add]').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      const productId = parseInt(this.dataset.add);
      const variantId = parseInt(this.dataset.variant) || 1;
      addToCart(productId, variantId);
    });
  });
}
function attachCardNav(){ /* delegated globally, no-op placeholder */ }

/* ================= TILT EFFECT ================= */
function attachTiltAll(){
  document.querySelectorAll('.wine-card').forEach(card=>{
    if(card.dataset.tiltBound) return;
    card.dataset.tiltBound = "1";
    card.addEventListener('mousemove', e=>{
      const r = card.getBoundingClientRect();
      const x = e.clientX - r.left, y = e.clientY - r.top;
      const rx = ((y / r.height) - .5) * -8;
      const ry = ((x / r.width) - .5) * 8;
      card.style.transform = `perspective(700px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-4px)`;
      card.style.setProperty('--mx', x+'px');
      card.style.setProperty('--my', y+'px');
    });
    card.addEventListener('mouseleave', ()=>{ card.style.transform = ''; });
  });
}

/* ================= SCROLL REVEAL ================= */
let observer;
function refreshReveal(){
  if(observer) observer.disconnect();
  observer = new IntersectionObserver(entries=>{
    entries.forEach(en=>{ if(en.isIntersecting) en.target.classList.add('in-view'); });
  }, {threshold:.12});
  document.querySelectorAll('[data-reveal]').forEach(el=>observer.observe(el));
}

/* ================= HEADER SCROLL STATE ================= */
window.addEventListener('scroll', ()=>{
  document.getElementById('siteHeader').classList.toggle('scrolled', window.scrollY > 30);
});

/* ================= MOBILE NAV ================= */
document.getElementById('burger').addEventListener('click', ()=>{
  document.getElementById('mainNav').classList.toggle('open');
});

/* ================= SEARCH (navbar + shop page) ================= */
const navSearch = document.getElementById('navSearch');
const navSearchBtn = document.getElementById('navSearchBtn');
const navSearchPanel = document.getElementById('navSearchPanel');
const navSearchForm = document.getElementById('navSearchForm');
const navSearchInput = document.getElementById('navSearchInput');

if(navSearchBtn){
    navSearchBtn.addEventListener('click', function(e){
        e.stopPropagation();
        navSearch.classList.toggle('active');
        if(navSearch.classList.contains('active')){
            setTimeout(()=>navSearchInput.focus(), 60);
        }
    });
    navSearchPanel.addEventListener('click', function(e){
        e.stopPropagation();
    });
    document.addEventListener('click', function(){
        navSearch.classList.remove('active');
    });
    const navSearchClose = document.getElementById('navSearchClose');
    if(navSearchClose){
        navSearchClose.addEventListener('click', function(e){
            e.stopPropagation();
            navSearch.classList.remove('active');
        });
    }
    navSearchInput.addEventListener('input', ()=>{
        renderSearchResults(document.getElementById('navSearchResults'), navSearchInput.value);
    });
    navSearchForm.addEventListener('submit', function(e){
        e.preventDefault();
        const val = navSearchInput.value;
        const {pages, products} = searchSite(val);
        navSearch.classList.remove('active');
        document.getElementById('navSearchResults').innerHTML = '';
        if(pages.length && (!products.length || pages[0].score >= products[0].score)){
            showView(pages[0].nav);
        } else {
            currentSearch = val;
            currentFilter = 'all';
            showView('products');
        }
    });
}

const shopSearchInput = document.getElementById('shopSearchInput');
const mobileNavSearchForm = document.getElementById('mobileNavSearchForm');
if(mobileNavSearchForm){
  mobileNavSearchForm.addEventListener('submit', function(e){
    e.preventDefault();
    const val = document.getElementById('mobileNavSearchInput').value;
    const {pages, products} = searchSite(val);
    document.getElementById('mainNav').classList.remove('open');
    if(pages.length && (!products.length || pages[0].score >= products[0].score)){
        showView(pages[0].nav);
    } else {
        currentSearch = val;
        currentFilter = 'all';
        showView('products');
    }
  });
}

const shopSearchBar = document.getElementById('shopSearchBar');
const shopSearchClear = document.getElementById('shopSearchClear');

if(shopSearchInput){
  shopSearchInput.addEventListener('input', ()=>{
    currentSearch = shopSearchInput.value;
    shopSearchBar.classList.toggle('has-value', !!currentSearch);
    applyProductFilters();
  });
}
if(shopSearchClear){
  shopSearchClear.addEventListener('click', ()=>{
    currentSearch = '';
    shopSearchInput.value = '';
    shopSearchBar.classList.remove('has-value');
    applyProductFilters();
    shopSearchInput.focus();
  });
}

/* ================= CONTACT FORM ================= */
document.getElementById('contactForm').addEventListener('submit', e=>{
  e.preventDefault();
  document.getElementById('contactForm').style.display = 'none';
  document.getElementById('formSuccess').classList.add('show');
  setTimeout(()=>{
    document.getElementById('contactForm').reset();
    document.getElementById('contactForm').style.display = 'block';
    document.getElementById('formSuccess').classList.remove('show');
  }, 4000);
});

/* ================= SCROLL PROGRESS ================= */
function updateScrollProgress(){
  const h = document.documentElement;
  const scrolled = h.scrollTop;
  const height = h.scrollHeight - h.clientHeight;
  const pct = height > 0 ? (scrolled/height)*100 : 0;
  document.getElementById('scrollProgress').style.width = pct + '%';
}
window.addEventListener('scroll', updateScrollProgress);

/* ================= CUSTOM CURSOR ================= */
if(window.matchMedia('(hover:hover) and (pointer:fine)').matches){
  const dot = document.getElementById('cursorDot');
  const ring = document.getElementById('cursorRing');
  let rx=0, ry=0, mx=0, my=0;
  window.addEventListener('mousemove', e=>{
    dot.style.left = e.clientX+'px'; dot.style.top = e.clientY+'px';
    mx = e.clientX; my = e.clientY;
  });
  (function loop(){
    rx += (mx-rx)*0.18; ry += (my-ry)*0.18;
    ring.style.left = rx+'px'; ring.style.top = ry+'px';
    requestAnimationFrame(loop);
  })();
  document.addEventListener('mouseover', e=>{
    if(e.target.closest('a,button,[data-nav],[data-add],.wine-card')) ring.classList.add('big');
  });
  document.addEventListener('mouseout', e=>{
    if(e.target.closest('a,button,[data-nav],[data-add],.wine-card')) ring.classList.remove('big');
  });
}

/* ================= INIT ================= */
renderFeatured();
attachTiltAll();
showView('home');
updateCartBadge(false);
setTimeout(()=>document.getElementById('glassWrap').classList.add('filled'), 900);

window.addEventListener('load', ()=>{
  const curtain = document.getElementById('curtain');
  setTimeout(()=>{
    curtain.classList.add('open');
    setTimeout(()=>curtain.classList.add('hide'), 1100);
  }, 500);
});
// Fallback in case 'load' already fired
if(document.readyState === 'complete'){
  const curtain = document.getElementById('curtain');
  setTimeout(()=>{ curtain.classList.add('open'); setTimeout(()=>curtain.classList.add('hide'), 1100); }, 500);
}
</script>
<!-- Floating WhatsApp Button 9827156133 -->
<a href="https://wa.me/9779827156133"
   class="whatsapp-float"
   target="_blank"
   rel="noopener"
   aria-label="Chat with us on WhatsApp">
    
    <svg viewBox="0 0 32 32" width="32" height="32" fill="white">
        <path d="M16.001 3C8.821 3 3 8.82 3 16c0 2.29.596 4.526 1.728 6.497L3 29l6.664-1.696A12.94 12.94 0 0 0 16 29c7.18 0 13-5.82 13-13S23.18 3 16.001 3zm0 23.667c-2.078 0-4.11-.56-5.878-1.62l-.421-.25-3.956 1.007 1.057-3.856-.275-.446A10.58 10.58 0 0 1 5.333 16c0-5.883 4.785-10.667 10.668-10.667S26.667 10.117 26.667 16 21.884 26.667 16.001 26.667zm5.85-7.994c-.32-.16-1.895-.936-2.188-1.043-.293-.107-.506-.16-.72.16-.213.32-.826 1.043-1.013 1.256-.187.213-.373.24-.693.08-1.895-.947-3.14-1.693-4.39-3.84-.33-.568.33-.527.947-1.756.107-.213.053-.4-.027-.56-.08-.16-.72-1.736-.986-2.376-.26-.625-.526-.54-.72-.55l-.613-.011a1.176 1.176 0 0 0-.853.4c-.293.32-1.12 1.096-1.12 2.672s1.146 3.1 1.306 3.313c.16.213 2.257 3.447 5.47 4.837.763.33 1.359.527 1.824.674.766.243 1.463.209 2.014.127.614-.091 1.895-.773 2.162-1.523.267-.75.267-1.39.187-1.523-.08-.133-.293-.213-.613-.373z"/>
    </svg>

</a>
</body>
</html>