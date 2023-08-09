<?php
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use LZCompressor\LZString;


define('constant','http://localhost:8888/suyoto/bridging/apps/');
//use your own bpjs config
define('cons_id','23892');
define('secret_key','5vKB4C5C9C');
define('user_key','84a25986e67e111e8a822b21ef346976');
define('uri_antrean','https://apijkn.bpjs-kesehatan.go.id/antreanrs/');

/**
 * VCLAIM
 */
//Production
$vclaim_config = [
   'cons_id'       => '23892',
   'secret_key'    => '5vKB4C5C9C',
   'user_key'      => '84a25986e67e111e8a822b21ef346976',
   'base_url'      => 'https://apijkn.bpjs-kesehatan.go.id',
   'service_name'  => 'vclaim-rest'
];
//Development
// $vclaim_config = [
//     'cons_id'       => '5570',
//     'secret_key'    => 'j8EYrdO5Du',
//     'user_key'      => '5a99c5be5873d4a3c82e21c14ac30e39',
//     'base_url'      => 'https://apijkn-dev.bpjs-kesehatan.go.id',
//     'service_name'  => 'vclaim-rest-dev'
// ];

/**
 * Antrian Online
 */
$antreanrs_config = [
    'cons_id'       => '23892',
    'secret_key'    => '5vKB4C5C9C',
    'user_key'      => '84a25986e67e111e8a822b21ef346976',
    'base_url'      => 'https://apijkn.bpjs-kesehatan.go.id',
    'service_name'  => 'antreanrs'
 ];
/**
 * APLI CARE
 */
$aplicare_config = [
    'cons_id'      => '23892',
    'secret_key'   => '5vKB4C5C9C',
    'base_url'     => 'https://new-api.bpjs-kesehatan.go.id:8080',
    'service_name' => 'aplicaresws/rest'
];

/**
 * P-CARE
 */
$pcare_config = [
    'cons_id'      => '',
    'secret_key'   => '',
    'base_url'     => 'https://dvlp.bpjs-kesehatan.go.id:9081',
    'service_name' => 'pcare-rest-v3.0',
    'pcare_user'   => '',
    'pcare_pass'   => '',
    'kd_aplikasi'  => ''
];

/**
 * SITB_CONF
*/
$sitb_conf = [
        'cons_id'       => '',
        'user_pass'      => '',
        'base_url'      => 'http://sirs.yankes.kemkes.go.id',
        'service_name'  => 'sirsservice/sitbtraining/sitb'
    ];

// use Referensi service
//echo "<pre>";

$data = [
    ["make"=> "BMW", "model"=> "M4", "year"=> 2019, "name"=> "ccdeb89695"],
    ["make"=>"Renault", "model"=> "Kwid", "year"=> 2022, "name"=> "79c1eb5a4b"]
];
//$referensi = new Bridging\Bpjs\VClaim\Referensi($vclaim_config);
//echo json_encode($referensi->poli('icu'));
//echo json_encode($data);
//$peserta = new Bridging\Bpjs\VClaim\Peserta($vclaim_config);
//print_r($peserta->getByNoKartu('0001481590315','1989-12-29'));



//print_r($data);
//$referensi->post('antrean/add', $data);
//$referensi = $referensi->addAntrean($data);
//echo json_encode($response);
function timestamp(){
    $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
    $timestamp = (string)$dateTime->getTimestamp();
    return $timestamp;
}
function setHeader($consid,$secret,$userkey){
    $data = $consid. '&' . timestamp();
    $signature = hash_hmac('sha256', $data, $secret, true);
    $encodedSignature = base64_encode($signature);
    $response = [
        'X-cons-id' => $consid,
        'X-timestamp' => timestamp(),
        'X-signature' => $encodedSignature,
        'user_key' => $userkey,
        'Content-Type' => 'application/json'
    ];
   
    return $response;
}
function _decompress($consid,$secret,$txt)
{
    $key  = $consid . $secret. timestamp();
    $hash = hex2bin(hash('sha256', $key));
    $iv   = substr($hash, 0, 16);
    $tmp  = openssl_decrypt(base64_decode($txt), 'AES-256-CBC', $hash, OPENSSL_RAW_DATA, $iv);
    if ($tmp === false) return $txt;
    return LZString::decompressFromEncodedURIComponent($tmp);
}


Leaf\View::attach(Leaf\Veins::class);
app()->group('/antrean', function () {
    //display
    app()->get("/", function () {
        echo app()->veins->render("home",['title' => "Test"]);
    });
    app()->get("/check_in_bpjs", function () {
        echo app()->veins->render("display_checkin_bpjs",['title' => "Test"]);
    });
    /**
     * Referensi Jadwal Dokter
     */
    app()->get("/jadwaldokter", function () {
        $client = new Client();
        $headers = setHeader(cons_id,secret_key,user_key);
        try {
            $request = new Request(
                "GET",
                uri_antrean.'ref/poli',
                $headers
            );
            $response = json_decode($client->sendAsync($request)->wait()->getBody()->getContents(),true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getCode() == 0) {
                $handlerContext = $e->getHandlerContext();
                $response = [
                    'metadata' => [
                        'code' => $handlerContext['errno'],
                        'message' => $handlerContext['error']
                    ]
                ];
            } else
                $response = [
                    'metadata' => [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ]
                ];
        }
    
        if ($response['metadata']['code'] ?? '' == '200' and !empty($response['response']) and is_string($response['response']))
            $response['response'] = json_decode(_decompress(cons_id,secret_key,$response['response']), true);
        echo app()->veins->render("reff_jadwaldokter",['res_poli'=>$response]);
    });
    app()->post("/jadwaldokter", function () {
        $poli = request()->get("poli");
        $tanggal = request()->get("tanggal");
        $client = new Client();
        $headers = setHeader(cons_id,secret_key,user_key);
        try {
            $request = new Request(
                "GET",
                uri_antrean.'jadwaldokter/kodepoli/'.$poli.'/tanggal/'.$tanggal,
                $headers
            );
            $response = json_decode($client->sendAsync($request)->wait()->getBody()->getContents(),true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getCode() == 0) {
                $handlerContext = $e->getHandlerContext();
                $response = [
                    'metadata' => [
                        'code' => $handlerContext['errno'],
                        'message' => $handlerContext['error']
                    ]
                ];
            } else
                $response = [
                    'metadata' => [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ]
                ];
        }
    
        if ($response['metadata']['code'] ?? '' == '200' and !empty($response['response']) and is_string($response['response']))
            $response['response'] = json_decode(_decompress(cons_id,secret_key,$response['response']), true);
        $no=0;            
        $html = "";
       
        if(!empty($response['response'])){
            $html .="<table class='table table-hover no-cellpadding w-auto small'>
            <thead>
            <tr>
             <th>NO</th>
                 <th>KODE DOKTER</th>
                 <th>NAMA DOKTER</th>
                 <th>KODE POLI</th>
                 <th>NAMA POLI</th>
                 <th>KODE SPESIALIS</th>
                 <th>NAMA SPESIALIS</th>
                 <th>HARI PRAKTEK</th>
                 <th>WAKTU PRAKTEK</th>
                 <th>MAX PASIEN</th>
                 </tr>
             </thead><tbody>";
            
            foreach($response['response'] as $jdwl){
                $html.="<tr>
                    <td>".++$no."</td>
                    <td>". $jdwl['kodedokter']."</td>
                    <td>".$jdwl['namadokter']."</td>
                    <td>".$jdwl['kodepoli']."</td>
                    <td>".$jdwl['namapoli']."</td>
                    <td>".$jdwl['kodesubspesialis']."</td>
                    <td>".$jdwl['namasubspesialis']."</td>
                    <td>".$jdwl['namahari']."</td>
                    <td>".$jdwl['jadwal']."</td>
                    <td>".$jdwl['kapasitaspasien']."</td>
                </tr>";
            }
            $html.="</tbody></table>";
        }else{
            $html .= "<p>Tidak Ada Jadwal Untuk Poli dan tanggal tersebut</p>";
        }
        response()->markup($html);
        
    });
    app()->get("/ambilantrian", function () {
        $date = date('m/d/Y h:i:s a', time());
        echo app()->veins->render("ambil_antrean",['date'=>$date]);
    });
    app()->post("/antrean", function () {
        
        /**
         * Antrian Online
         */
       
        // $config = [
        //     'cons_id'       => $request_headers['Consid'],
        //     'secret_key'    => $request_headers['Secret'],
        //     'user_key'      => $request_headers['Userkey'],
        //     'base_url'      => 'https://apijkn.bpjs-kesehatan.go.id/antreanrs/',
        
        // ];
    
        // $client = new Client();
        // $headers = setHeader($config['cons_id'],$config['secret_key'],$config['user_key']);
        
        // $body = '{
        //     "kodebooking": "11111",
        //     "jenispasien": "JKN",
        //     "nomorkartu": "0002338018558",
        //     "nik": "3174054405750011",
        //     "nohp": "081219112011",
        //     "kodepoli": "THT",
        //     "namapoli": "THT-KL",
        //     "pasienbaru": 0,
        //     "norm": "25-74-17",
        //     "tanggalperiksa": "2023-07-17",
        //     "kodedokter": 360559,
        //     "namadokter": "YOKE KURNIAWAN INARDI",
        //     "jampraktek": "08:00-12:00",
        //     "jeniskunjungan": 2,
        //     "nomorreferensi": "0112R0800523B000033",
        //     "nomorantrean": "A-01",
        //     "angkaantrean": 1,
        //     "estimasidilayani": 1689556200000,
        //     "sisakuotajkn": 30,
        //     "kuotajkn": 30,
        //     "sisakuotanonjkn": 10,
        //     "kuotanonjkn": 10,
        //     "keterangan": "Peserta harap 30 menit lebih awal guna pencatatan administrasi."
        // }';
    
        // try {
        //     $request = new Request(
        //         request()->getMethod(),
        //         $config['base_url'].$request_headers['Service'],
        //         $headers,
        //         json_encode($request_body)
        //     );
        //     $response = json_decode($client->sendAsync($request)->wait()->getBody()->getContents(),true);
        // } catch (\GuzzleHttp\Exception\RequestException $e) {
        //     if ($e->getCode() == 0) {
        //         $handlerContext = $e->getHandlerContext();
        //         $response = [
        //             'metadata' => [
        //                 'code' => $handlerContext['errno'],
        //                 'message' => $handlerContext['error']
        //             ]
        //         ];
        //     } else
        //         $response = [
        //             'metadata' => [
        //                 'code' => $e->getCode(),
        //                 'message' => $e->getMessage(),
        //                 'test'=>$request_body
        //             ]
        //         ];
        // }
    
        // if ($response['metadata']['code'] ?? '' == '200' and !empty($response['response']) and is_string($response['response']))
        //     $response['response'] = json_decode(_decompress($config['cons_id'],$config['secret_key'],$response['response']), true);

        // response()->json($response);
    });
    app()->get("/setHeader", function () {
        $consid = 23892;
        $secret = '5vKB4C5C9C';
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $timestamp = (string)$dateTime->getTimestamp();
        $data = $consid. '&' . $timestamp;
        $signature = hash_hmac('sha256', $data, $secret, true);
        $encodedSignature = base64_encode($signature);
        $a = "X-cons-id: ".$consid;
        $b = "X-timestamp: ".$timestamp;
        $c = "X-signature: ".$encodedSignature;
        $d = "user_key: 84a25986e67e111e8a822b21ef346976";
        $response = $a."<br/>".$b."<br/>".$c."<br/>".$d."<br/>";  
        response()->markup($response);
    });
});

app()->get("/", function () {
    echo app()->veins->render("index");
});
app()->get("/catalog", function () {
    echo app()->veins->render("catalog",['title' => "Test"]);
});

app()->run();