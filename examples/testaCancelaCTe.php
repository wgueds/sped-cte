<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once '../bootstrap.php';

use NFePHP\CTe\Make;
use NFePHP\CTe\Tools;
use NFePHP\CTe\Complements;
use NFePHP\Common\Certificate;
use NFePHP\CTe\Common\Standardize;

//tanto o config.json como o certificado.pfx podem estar
//armazenados em uma base de dados, então não é necessário 
///trabalhar com arquivos, este script abaixo serve apenas como 
//exemplo durante a fase de desenvolvimento e testes.
$arr = [
    "atualizacao" => "2016-11-03 18:01:21",
    "tpAmb" => 2,
    "razaosocial" => "SUA RAZAO SOCIAL LTDA",
    "cnpj" => "86933033000100",
    "siglaUF" => "RS",
    "schemes" => "PL_CTe_300",
    "versao" => '3.00',
    "proxyConf" => [
        "proxyIp" => "",
        "proxyPort" => "",
        "proxyUser" => "",
        "proxyPass" => ""
    ]
];
//monta o config.json
$configJson = json_encode($arr);

//carrega o conteudo do certificado.
$content = file_get_contents('fixtures/certificado.pfx');

try {
  $tools = new Tools($configJson, Certificate::readPfx($content, '02040608'));
  $tools->model('57');

  $chave = '43171086933033000100570020000000011766063280';
  $xJust = 'Valor do frete incorreto.';
  $nProt = '143170000723850';
  $response = $tools->sefazCancela($chave, $xJust, $nProt);
//     header('Content-type: text/xml; charset=UTF-8');
//print_r($response);
//exit();
//    
  //você pode padronizar os dados de retorno atraves da classe abaixo
  //de forma a facilitar a extração dos dados do XML
  //NOTA: mas lembre-se que esse XML muitas vezes será necessário, 
  //      quando houver a necessidade de protocolos
  $stdCl = new Standardize($response);
  //nesse caso $std irá conter uma representação em stdClass do XML retornado
  $std = $stdCl->toStd();
  //nesse caso o $arr irá conter uma representação em array do XML retornado
  $arr = $stdCl->toArray();
  //nesse caso o $json irá conter uma representação em JSON do XML retornado
  $json = $stdCl->toJson();

  echo "<pre>";
  print_r($arr);
//    exit();
  //verifique se o evento foi processado
  if ($std->infEvento->cStat != 128) {
    //houve alguma falha e o evento não foi processado
    echo $std->infEvento->xMotivo;
  } else {
    $cStat = $std->infEvento->cStat;
    if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
      //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
      $xml = Complements::toAuthorize($tools->lastRequest, $response);
      //grave o XML protocolado e prossiga com outras tarefas de seu aplicativo
      $filename = "xml/canceladas/{$chave}-procEvento.xml";
      file_put_contents($filename, $auth);
    } else {
      //houve alguma falha no evento 
      //TRATAR
    }
  }
} catch (\Exception $e) {
  echo $e->getMessage();
  //TRATAR
}