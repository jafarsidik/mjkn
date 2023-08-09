<?php
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use LZCompressor\LZString;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Promise\Promise;

define('constant','http://localhost:8888/suyoto/bridging/apps/');
define('host_api_simrs','http://localhost');
define('host_api_apps_antrean','http://localhost');
define('api_key_apps_antrean','f6bdc2e0b09c046');
define('api_secret_apps_antrean','cab05108cb4badd');
//Konfigurasi BPJS

define('cons_id','23892');
define('secret_key','5vKB4C5C9C');
define('user_key','84a25986e67e111e8a822b21ef346976');
define('uri_antrean','https://apijkn.bpjs-kesehatan.go.id/antreanrs/');
define('uri_vclaim','https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/');

//$referensi = new Bridging\Bpjs\VClaim\Referensi($vclaim_config);
//echo json_encode($referensi->poli('icu'));
//echo json_encode($data);
//$peserta = new Bridging\Bpjs\VClaim\Peserta($vclaim_config);
//print_r($peserta->getByNoKartu('0001481590315','1989-12-29'));



//$response = $client->sendAsync($request)->wait()->getBody()->getContents();



Leaf\View::attach(Leaf\Veins::class);

app()->get("/", function () {
    echo app()->veins->render("catalog",['title' => "Test"]);
    
});

app()->group('/antrean', function () {
    app()->get("/token", function () {
        $head_username = request()->headers('x-username');
        $head_password = request()->headers('x-password');
        
        if( ($head_username == 'jafar') and ($head_password=="jafar") ){
            $key = 'JafarSidik89!@#$%^&*()~';
            $payload = [
                'iss' => 'bpjs',
                'aud' => 'bpjs',
                'iat' => 1356999524,
                'nbf' => 1357000000
            ];
            
            $token = JWT::encode($payload, $key, 'HS256');
            $json = ["response"=>["token"=>$token],"metadata"=>["message"=>"Ok","code"=>200]];
            response()->json($json);
        }else{
            $json = ["metadata"=>["message"=>"Authentecation Failed","code"=>201]];
            response()->json($json);
        }
    });
    app()->post("/status-antrean", function () {
        $client = new Client();
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
            //example Request
            /**
             * {
             *  "kodepoli": "ANA",
             *  "kodedokter": 12346,
             *  "tanggalperiksa": "2020-01-28",
             *  "jampraktek": "08:00-16:00"
             * }
             */
            $kodepoli = $body['kodepoli'];
            $kodedokter = $body['kodedokter'];
            $tanggalperiksa = $body['tanggalperiksa'];
            $jampraktek = $body['jampraktek'];
            
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */
            
            //GetJadwal Dokter Poli Direct Call API BPJS Antrean VIA aplikasi Antrean Frappe Frameworks
            $req_jadwal_dokter = new Request(
                "POST",
                host_api_apps_antrean.'/api/method/antrean.aplikasi_antrean_sistem_informasi_rumah_sakit.antrean.send',
                [
                    'Authorization' => 'token '.api_key_apps_antrean.':'.api_secret_apps_antrean.'',
                    'Content-Type'=>'application/json'
                ],
                json_encode([
                    'service'   => 'jadwaldokter/kodepoli/'.$kodepoli.'/tanggal/'.$tanggalperiksa,
                    'method'    => 'GET'
                ])
            );
            $res_jadwal_dokter = $client->sendAsync($req_jadwal_dokter)->wait()->getBody()->getContents();    
           
           
            //Call Antrian Dokter Ke Aplikasi Antrian
            $request_data = new Request(
                "GET",
                host_api_apps_antrean.'/api/resource/Antrean BPJS?filters=[["kode_poli", "=", "'.$kodepoli.'"], ["kodedokter", "=", "'.$kodedokter.'"], ["tanggalperiksa", "=", "'.$tanggalperiksa.'"],["jampraktek", "=", "'.$jampraktek.'"]]&order_by=creation desc&limit=1',
                ['Authorization' => 'token '.api_key_apps_antrean.':'.api_secret_apps_antrean.'']
            );
            $respon_request_data = json_decode($client->sendAsync($request_data)->wait()->getBody()->getContents());
            
            if(count($respon_request_data->data) > 0){
                $getByID = new Request(
                   "GET",
                   host_api_apps_antrean.'/api/resource/Antrean BPJS/'.$respon_request_data->data[0]->name,
                    ['Authorization' => 'token '.api_key_apps_antrean.':'.api_secret_apps_antrean.'']
                );          
                $response = json_decode($client->sendAsync($getByID)->wait()->getBody()->getContents());
                print_r($response);
                $json = json_decode('{
                    "response": {
                        "namapoli": "'.$response->data->nama_poli.'",
                        "namadokter": "'.$response->data->namadokter.'",
                        "totalantrean": '.count($respon_request_data->data).',
                        "sisaantrean": 234,
                        "antreanpanggil": '.$response->data->noantrian.',
                        "sisakuotajkn": 5,
                        "kuotajkn": ,
                        "sisakuotanonjkn": 5,
                        "kuotanonjkn": 30,
                        "keterangan": ""
                    },
                    "metadata": {
                        "message": "Ok",
                        "code": 200
                    }
                }');
            }else{
                
            $json = json_decode('{
                
                "metadata": {
                    "message": "Data Not Found",
                    "code": 99
                }
            }');
            }
            
            
            
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/ambil-antrean", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "response": {
                "nomorantrean": "A-12",
                "angkaantrean": 12,
                "kodebooking": "16032021A001",
                "norm": "123345",
                "namapoli": "Anak",
                "namadokter": "Dr. Hendra",
                "estimasidilayani": 1615869169000,
                "sisakuotajkn": 5,
                "kuotajkn": 30,
                "sisakuotanonjkn": 5,
                "kuotanonjkn": 30,
                "keterangan": "Peserta harap 60 menit lebih awal guna pencatatan administrasi."
                },
                "metadata": {
                "message": "Ok",
                "code": 200
                }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/sisa-antrean", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
            "response": {
                "nomorantrean": "A20",
                "namapoli": "Anak",
                "namadokter": "Dr. Hendra",
                "sisaantrean": 12,
                "antreanpanggil": "A-8",
                "waktutunggu": 9000,
                "keterangan": ""
            },
            "metadata": {
                "message": "Ok",
                "code": 200
            }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });

    app()->post("/batal", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "metadata": {
                   "message": "Ok",
                   "code": 200
                }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/checkin", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "metadata": {
                    "code": 200,
                    "message": "OK"
                 }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/info-pasien-baru", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "response": {
                   "norm": "123456"
                },
                "metadata": {
                   "message": "Harap datang ke admisi untuk melengkapi data rekam medis",
                   "code": 200
                }
             }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/jadwal-operasi-rs", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "response": {
                    "list" : [{
                         "kodebooking": "123456ZXC",
                         "tanggaloperasi": "2019-12-11",
                         "jenistindakan": "operasi gigi",
                         "kodepoli": "001",
                         "namapoli": "Poli Bedah Mulut",
                         "terlaksana": 1,
                         "nopeserta": "0000000924782",
                         "lastupdate": 1577417743000 
                    },
                    {
                         "kodebooking": "67890QWE",
                         "tanggaloperasi": "2019-12-11",
                         "jenistindakan": "operasi mulut",
                         "kodepoli": "001",
                         "namapoli": "Poli Bedah Mulut",
                         "terlaksana": 0,
                         "nopeserta": "",
                         "lastupdate": 1577417743000
                    }]
                },
                "metadata": {
                    "message": "Ok",
                    "code": 200
                }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/jadwal-operasi-pasien", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "response": {
                    "list" : [{
                         "kodebooking": "123456ZXC",
                         "tanggaloperasi": "2019-12-11",
                         "jenistindakan": "operasi gigi",
                         "kodepoli": "001",
                         "namapoli": "Poli Bedah Mulut",
                         "terlaksana": 0 
                    }]
                },
                "metadata": {
                    "message": "Ok",
                    "code": 200
                }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/ambil-antrean-farmasi", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "response": {
                    "jenisresep": "Racikan/Non Racikan",
                    "nomorantrean": 1,
                    "keterangan": ""
                },
                "metadata": {
                    "message": "Ok",
                    "code": 200
                }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
    app()->post("/status-antrean-farmasi", function () {
        $key = 'JafarSidik89!@#$%^&*()~';
        $head_username = request()->headers('x-username');
        $head_token = request()->headers('x-token');
        try {
            JWT::decode($head_token, new key($key,'HS256'));
            $body = request()->body();
    
            /**
             * Disini di isi fungsi call simrs aplikasi antrian
             * 
             */

            $json = json_decode('{
                "response": {
                    "jenisresep": "Racikan/Non Racikan",
                    "totalantrean": 10,
                    "sisaantrean": 8,
                    "antreanpanggil": 2,
                    "keterangan": ""
                },
                "metadata": {
                    "message": "Ok",
                    "code": 200
                }
            }');
        
        } catch (InvalidArgumentException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>201]];
        } catch (DomainException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>202]];
        } catch (SignatureInvalidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>203]];
        } catch (BeforeValidException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>204]];
        } catch (ExpiredException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>205]];
        } catch (UnexpectedValueException $e) {
            $json = ["metadata"=>["message"=>$e->getMessage(),"code"=>206]];
        }
        response()->json($json);
    });
});

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

app()->run();
