#!/bin/sh
# Runs one concurrency scenario. Usage: run.sh <label> <balance> <orderCount> <orderPrice> <workersPerOrder>
DIR=$(dirname "$0")
export BENCH_DIR="$DIR"
# Needs MySQL (row locks) and a scratch database, e.g.:
#   DB_CONNECTION=mysql DB_DATABASE=lazyroom_bench php artisan migrate:fresh
#   DB_CONNECTION=mysql DB_DATABASE=lazyroom_bench sh benchmarks/concurrency/run.sh "same order x20" 1000 1 60 20
label=$1; balance=$2; count=$3; price=$4; per=$5
setup=$(php "$DIR/setup.php" "$balance" "$count" "$price")
customer=$(echo "$setup" | node -e "let s='';process.stdin.on('data',d=>s+=d).on('end',()=>console.log(JSON.parse(s).customer))")
orders=$(echo "$setup" | node -e "let s='';process.stdin.on('data',d=>s+=d).on('end',()=>console.log(JSON.parse(s).orders.join(' ')))")
start=$(node -e "console.log(Math.round((Date.now()+6000)*1000))")
out="$DIR/out-$$.txt"; : > "$out"
for o in $orders; do
  i=0
  while [ $i -lt "$per" ]; do php "$DIR/worker.php" "$customer" "$o" "$start" > "$out.$o.$i" 2>&1 & i=$((i+1)); done
done
wait; cat "$out".* > "$out"; rm -f "$out".*
php -r '
require getenv("BENCH_DIR")."/boot.php";
$c = DB::table("users")->where("email","c@bench.test")->value("balance");
$s = DB::table("users")->where("email","s@bench.test")->value("balance");
$done = DB::table("orders")->where("state","complete")->count();
echo json_encode(["customer_balance"=>$c, "supplier_balance"=>$s, "orders_complete"=>$done]), PHP_EOL;' > "$out.final"
node -e "
const fs=require('fs');const lines=fs.readFileSync('$out','utf8').trim().split(/\r?\n/).filter(l=>l.startsWith('{')).map(JSON.parse);
const ok=lines.filter(l=>l.status===200).length;const by={};lines.forEach(l=>{const k=l.status+' '+l.message.replace(/[0-9.]+$/,'');by[k]=(by[k]||0)+1});
console.log(JSON.stringify({scenario:'$label',requests:lines.length,succeeded:ok,responses:by,final:JSON.parse(fs.readFileSync('$out.final','utf8'))}))"
rm -f "$out" "$out.final"
