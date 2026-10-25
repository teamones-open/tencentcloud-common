<?php
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){if((error_reporting()&$severity)||in_array($severity,[E_DEPRECATED,E_USER_DEPRECATED],true))throw new ErrorException($message,0,$severity,$file,$line);return false;});
$root=dirname(__DIR__);
require ($argv[1] ?? $root.'/vendor/autoload.php');
spl_autoload_register(static function($class)use($root){if(str_starts_with($class,'TencentCloud\\Common\\')){ $file=$root.'/src/'.str_replace('\\','/',$class).'.php';if(is_file($file))require$file;}},true,true);
$count=0;
$check=static function($actual,$expected,$label)use(&$count){++$count;if($actual!==$expected)throw new RuntimeException($label);};
$request=new TencentCloud\Kms\V20190118\Models\DecryptRequest();
$request->fromJsonString('{"CiphertextBlob":"test-cipher"}');
$check($request->serialize(),['CiphertextBlob'=>'test-cipher'],'KMS serialization');
$check(json_decode($request->toJsonString(),true),['CiphertextBlob'=>'test-cipher'],'KMS JSON');
$response=new TencentCloud\Kms\V20190118\Models\DecryptResponse();
$response->fromJsonString('{"Plaintext":"aGVsbG8=","RequestId":"test"}');
$check(base64_decode($response->getPlaintext()),'hello','KMS plaintext response');
$profile=new TencentCloud\Common\Profile\ClientProfile();
$profile->getHttpProfile()->setEndpoint('kms.tencentcloudapi.com');
$client=new TencentCloud\Kms\V20190118\KmsClient(new TencentCloud\Common\Credential('test-id','test-key'),'ap-guangzhou',$profile);
$check(get_class($client),TencentCloud\Kms\V20190118\KmsClient::class,'KMS client construction');
$voice=new TencentCloud\Vms\V20200902\Models\SendTtsVoiceRequest();
$voice->fromJsonString('{"TemplateId":"123","TemplateParamSet":["hello"],"CalledNumber":"+8613800138000","VoiceSdkAppid":"456"}');
$check($voice->serialize()['TemplateParamSet'],['hello'],'voice nested serialization');
$model=new class extends TencentCloud\Common\AbstractModel {
    private $Hidden='private';
    public $Empty=null;
    public $Nested;
    public function deserialize($param){}
};
$model->Nested=[$request];
$check($model->serialize(),['Hidden'=>'private','Nested'=>[['CiphertextBlob'=>'test-cipher']]],'private/nested/null contract');
$reflection=new ReflectionMethod(TencentCloud\Common\AbstractClient::class,'getPrivateMethod');
$private=$reflection->invoke($client,$client,'formatSignString');
$check($private->invoke($client,'example.invalid','/',['b'=>2,'a'=>1],'get'),'GETexample.invalid/?a=1&b=2','private signing method');
$check(error_reporting(),E_ALL,'strict reporting');
echo json_encode(['passed'=>true,'assertions'=>$count,'php'=>PHP_VERSION]),"\n";
