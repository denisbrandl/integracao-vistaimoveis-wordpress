<?php
set_time_limit(0);
// ini_set('display_errors', 1);
require ('./classes/ImportacaoWordpress.php');


$integracao_vista_ultima_sincronizacao = get_option('integracao_vista_ultima_sincronizacao');
if ($integracao_vista_ultima_sincronizacao === FALSE || 1==1) {
	$integracao_vista_ultima_sincronizacao = date('Y-m-d', strtotime('-3 days'));
}

$somente_imovel = null;
if ( isset($_GET['codigo_imovel']) && !empty($_GET['codigo_imovel']) ) {
	$somente_imovel = $_GET['codigo_imovel'];
}

$fp = fopen('status.json', 'w');
fwrite($fp,'0');
fclose($fp);

$integracao_vista_chave_vista = get_option('integracao_vista_chave_vista');
if ($integracao_vista_chave_vista === FALSE) {
	die('Não foi encontrado o cadastro da chave de acesso ao Vista');
}

$integracao_vista_campos_infra_carac = get_option('integracao_vista_campos_infra_carac');
$arrInfraCustomizado = [];
if ($integracao_vista_campos_infra_carac !== FALSE) {
	$arrInfraCustomizado = json_decode($integracao_vista_campos_infra_carac, true);
}

$objimportacaowordpress = new ImportacaoWordpress();

// $url_integracao_vista = 'http://rodrigoo-rest.vistahost.com.br/imoveis/listar?key='.$integracao_vista_chave_vista.'&showtotal=1&pesquisa={"fields":["Codigo"],"filter":{"DataHoraAtualizacao":["'.$integracao_vista_ultima_sincronizacao.'", "'.date('Y-m-d').'"]},"order":{"DataHoraAtualizacao":"asc"},"paginacao":{"pagina":1,"quantidade":10}}';
// $url_integracao_vista = 'http://rodrigoo-rest.vistahost.com.br/imoveis/listar';

$metodo = 'GET';
// $metodo = 'POST';

$arrParametros = json_encode(
					[
						'fields' => 'Codigo',
						'filter' => ['DataHoraAtualizacao' => [$integracao_vista_ultima_sincronizacao, date('Y-m-d', strtotime('+1 day'))]],
						'order' => ['DataHoraAtualizacao' => 'asc'],
						'paginacao' => ['pagina' => '1', 'quantidade' => '10']
					]
				);

/*
$arrParametros = [
	'url' => $url_integracao_vista,
	'key' => $integracao_vista_chave_vista,
	'pesquisa' => json_encode(
					[
						'fields' => 'Codigo',
						'filter' => ['DataHoraAtualizacao' => [$integracao_vista_ultima_sincronizacao, date('Y-m-d')]],
						'order' => ['DataHoraAtualizacao' => 'asc'],
						'paginacao' => ['pagina' => '1', 'quantidade' => '10'],
						'showtotal' => '1'
					]
				)
];
*/

$url_integracao_vista = 'https://rodrigoo-rest.vistahost.com.br/imoveis/listar?key='.$integracao_vista_chave_vista.'&showtotal=1&pesquisa='.$arrParametros;
/*
$url_integracao_vista = sprintf(
	'%s',
	'http://projetos.minorsolucoes.com.br/integracao-vista/bridge-vista.php',
);
*/

$ignorar_cabecalho = FALSE;
// $ignorar_cabecalho = TRUE;
$produtos = $objimportacaowordpress->callCurl($url_integracao_vista, $arrParametros , $metodo, $ignorar_cabecalho);

$arrImoveis = json_decode($produtos, true);

$totalPaginas = $somente_imovel !== null ? $arrImoveis['paginas'] : 1;

$totalImoveis = $arrImoveis['total'];
$imovel_atual = 1;

$arrCampos = [
	"Codigo",
	"Categoria",
	"DataHoraAtualizacao",
	"ValorVenda",
	"ValorLocacao",
	"ValorCondominio",
	"ValorIptu",
	"Dormitorios",
	"TotalBanheiros",
	"AreaConstruida",
	"AreaPrivativa",
	"Empreendimento",
	"Bairro",
	"Endereco",
	"Cidade",
	"Pais",
	"CEP",
	"DescricaoWeb",
	"Situacao",
	"Status",
	"TipoImovel",
	"Categoria",
	"Vagas",
	"ExibirNoSite",
	"Caracteristicas",
	"InfraEstrutura",
	"GMapsLatitude",
	"GMapsLongitude",
	"PorteiroEletronico",
	array(
		"Foto" => [
			"Foto",
			"FotoPequena",
			"Destaque"
		]
	)
];

$arrInfraCustomizadoSemEspacoBranco = array_keys($arrInfraCustomizado);
foreach ($arrInfraCustomizadoSemEspacoBranco as $key => $value) {
	$arrInfraCustomizadoSemEspacoBranco[$key] = str_replace(' ','',$value);
}

$arrCampos = array_merge($arrCampos, $arrInfraCustomizadoSemEspacoBranco);

$paginaAtual = 1;

$arrProdutos = [];
unset($arrImoveis['total']);
unset($arrImoveis['paginas']);
unset($arrImoveis['pagina']);
unset($arrImoveis['quantidade']);
do {
	
	foreach ($arrImoveis as $imovel) {
		$arrProduto = array();
		
		if ($somente_imovel !== null) {
			$imovel['Codigo'] = $somente_imovel;
			echo '<h1> Buscando somente ' . $somente_imovel.'</h1>';
		}
		
		$arrParametros = json_encode(
			[
				'fields' => $arrCampos
			]
		);		
		
		
		$url_detalhe_imovel = 'http://rodrigoo-rest.vistahost.com.br/imoveis/detalhes?key='.$integracao_vista_chave_vista.'&imovel='.$imovel['Codigo'].'&pesquisa='.$arrParametros;
		// $url_detalhe_imovel = 'http://rodrigoo-rest.vistahost.com.br/imoveis/detalhes';

		$arrParametrosDetalhesImovel = [];
		/*
		$arrParametrosDetalhesImovel = [
			'url' => $url_detalhe_imovel,
			'key' => $integracao_vista_chave_vista,
			'imovel' => $imovel['Codigo'],
			'pesquisa' => json_encode(
							[
								'fields' => json_encode($arrCampos)
							]
						)
		];
		
		$url_detalhe_imovel = sprintf(
			'%s',
			'http://projetos.minorsolucoes.com.br/integracao-vista/bridge-vista.php',
		);		
		*/
		
		$detalheImovel = $objimportacaowordpress->callCurl($url_detalhe_imovel, [] ,$metodo);
		// $detalheImovel = $objimportacaowordpress->callCurl($url_detalhe_imovel, $arrParametrosDetalhesImovel ,$metodo, $ignorar_cabecalho);

		$arrDetalheImovel = json_decode($detalheImovel, true);
		
		$arrProduto['dados_gerais']['id_original'] = $arrDetalheImovel['Codigo'];
		$arrProduto['dados_gerais']['nome'] = sprintf(
			"%s - %s",
			!empty($arrDetalheImovel['Empreendimento']) ? $arrDetalheImovel['Empreendimento'] : $arrDetalheImovel['Bairro'],
			$arrDetalheImovel['Codigo']
		);	
		$arrProduto['dados_gerais']['descricao_completa'] = $arrDetalheImovel['DescricaoWeb'];	
		$arrProduto['dados_gerais']['valor_venda'] = $arrDetalheImovel['ValorVenda'];		
		$arrProduto['dados_gerais']['valor_locacao'] = $arrDetalheImovel['ValorLocacao'];				
		$arrProduto['dados_gerais']['valor_condominio'] = $arrDetalheImovel['ValorCondominio'];
		$arrProduto['dados_gerais']['valor_iptu'] = $arrDetalheImovel['ValorIptu'];
		$arrProduto['dados_gerais']['quantidade_quartos'] = $arrDetalheImovel['Dormitorios'];
		$arrProduto['dados_gerais']['quantidade_banheiros'] = $arrDetalheImovel['TotalBanheiros'];
		$arrProduto['dados_gerais']['quantidade_vagas'] = $arrDetalheImovel['Vagas'];
		$arrProduto['dados_gerais']['metragem'] = !empty($arrDetalheImovel['AreaPrivativa']) ? $arrDetalheImovel['AreaPrivativa'] : $arrDetalheImovel['AreaConstruida'];
		$arrProduto['dados_gerais']['nome_condominio'] = $arrDetalheImovel['Empreendimento'];
		$arrProduto['dados_gerais']['bairro'] = $arrDetalheImovel['Bairro'];
		$arrProduto['dados_gerais']['endereco'] = $arrDetalheImovel['Endereco'];
		$arrProduto['dados_gerais']['cidade'] = $arrDetalheImovel['Cidade'];
		$arrProduto['dados_gerais']['pais'] = $arrDetalheImovel['Pais'];
		$arrProduto['dados_gerais']['cep'] = $arrDetalheImovel['CEP'];
		$arrProduto['dados_gerais']['status'] = $arrDetalheImovel['Status'];
		$arrProduto['dados_gerais']['exibir'] = $arrDetalheImovel['ExibirNoSite'];
		$arrProduto['dados_gerais']['tipo'] = $arrDetalheImovel['Categoria'];
		$arrProduto['dados_gerais']['latitude'] = $arrDetalheImovel['GMapsLatitude'];
		$arrProduto['dados_gerais']['longitude'] = $arrDetalheImovel['GMapsLongitude'];

		$arrInfraCustomizadoRetorno = [];
		
		foreach ($arrInfraCustomizado as $infra_k => $infra_v) {
			if (!isset($arrDetalheImovel['InfraEstrutura'][$infra_k]) && !isset($arrDetalheImovel['Caracteristicas'][$infra_k])) {
				$arrInfraCustomizadoRetorno[$infra_k] = $arrDetalheImovel[$infra_k];
			}
		}
		
		$arrProduto['infraestrutura'] = array_merge(
			$arrDetalheImovel['InfraEstrutura'],
			$arrDetalheImovel['Caracteristicas'],
			$arrInfraCustomizadoRetorno
		);
		
		$arrProduto['imagens'] = $arrDetalheImovel['Foto'];
		
		$produtos = json_encode([$arrProduto], JSON_THROW_ON_ERROR);
		$a = $objimportacaowordpress->importarprodutos($produtos);
		
		$fp = fopen('status.json', 'w');
		fwrite($fp,
			json_encode(
				array(
					'total' => $totalImoveis,
					'current' => $imovel_atual++
				)
			)
		);
		fclose($fp);
		
		if ($somente_imovel !== null) {
			break;
		}
	}
	$paginaAtual++;

} while ($paginaAtual <= $totalPaginas);

update_option( 'integracao_vista_ultima_sincronizacao', date('Y-m-d') );
