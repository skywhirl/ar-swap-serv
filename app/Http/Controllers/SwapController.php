<?php

namespace App\Http\Controllers;

use App\Txn;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class SwapController extends Controller
{
    
    
    function swap() 
    {
        $txn = Txn::where('status', 'sent')->first();

        if(!$txn)
            $txn = Txn::where('status', 'failed')->first();

        if(!$txn)
            $txn = Txn::where('status', 'pending')->first();

        
        $arweave = new \Arweave\SDK\Arweave('http', '209.97.142.169', 1984);

        $transactionIds = $arweave->api()->arql([ 'op' => 'equals', 'expr1' => 'txn-eth-hash', 'expr2' => $txn->eth_txn ]); 
        
        if(count($transactionIds) > 0) {
            $txn->status = 'finished';
            $txn->save();
            return;

        }

        if($txn->status == 'sent')
            return;

        $ethUsd = $this->getEthPrice();

        $arCount = $ethUsd * $txn->eth;
        $arWinston =  (int) ($arCount * 1000000000000);


        $jwk = json_decode(file_get_contents(base_path().'/ar-1.json'), true);
        $wallet =  new \Arweave\SDK\Support\Wallet($jwk);

        $transaction = $arweave->createTransaction($wallet, 
            [ 'target' => $txn->ar_addr,
             'data' => '', 
             'quantity' => (string) $arWinston,
             'tags' => [ 
                 'txn-type' => 'AR-SWAP',
                 'txn-eth-hash' => $txn->eth_txn,
                 'txn-eth-addr' => $txn->eth_addr
                  ]
            ]);

        //$transaction = $arweave->createTransaction($wallet, [ 'target' => '5EN4sYqRlw0yKdR3lpBlZWHvfo520T0u1SNhwSJmG4g', 'quantity' => '100000000000', 'data' => '', 'tags' => [ 'txn-type' => 'AR-SWAPP' ]]);


        $txn->status = 'sent';
        $txn->ar = $arCount;
        $txn->ar_txn = $transaction->getAttribute('id');

        $arweave->api()->commit($transaction);  

        $txn->save();   
        dd($transaction);

        

    }

    function ethSync() 
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 
        'http://api-rinkeby.etherscan.io/api?module=account&action=txlist&address='.env('ETH_ADDR').'&startblock=0&endblock=99999999&sort=desc&apikey=YourApiKeyToken', [    
        ]);
        $response = json_decode($response->getBody()->getContents(), true);

        $txns = $response['result']; 
        echo '<pre>';
        foreach($txns as $txn) {
            print_r($txn);

            $newTxn = Txn::where('eth_txn', $txn['hash'])->first();
            if($newTxn) {
                break;
            }

            if(!ctype_xdigit(ltrim($txn['input'],'0x'))) 
                continue;

            $data = hex2bin(ltrim($txn['input'],'0x'));

            if (substr( $data, 0, 3 ) == "AR_")   {
                $arAddr = ltrim($data,'AR_');
                $eth = $txn['value'] / 1000000000000000000;
                Txn::create(
                    ['eth_txn'=> $txn['hash'], 
                    'data' => $txn['input'],
                    'ar_addr' => $arAddr, 
                    'eth' => $eth,
                    'eth_addr' => $txn['from'],
                    'status'=> 'pending'
                ]);
            }   


        }

    }

    function getEthPrice() 
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 
        'https://api.etherscan.io/api?module=stats&action=ethprice&apikey='+ env('ETHERSCAN_API_KEY')
        , [    
        ]);
        $response = json_decode($response->getBody()->getContents(), true);

        return $response['result']['ethusd'];
        
    }
}
