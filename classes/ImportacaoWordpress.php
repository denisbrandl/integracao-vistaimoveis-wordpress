<?php
$path = dirname(__DIR__, 4);
require_once($path."/wp-load.php");
require_once($path.'/wp-admin/includes/file.php');
require_once($path.'/wp-admin/includes/image.php' );

error_reporting(E_ALL);
ini_set('display_errors', 1);

class ImportacaoWordpress {
	
	public function retorno($sucesso,$mensagem) {
		return array('sucesso' => $sucesso, 'msgRetorno'=> $mensagem);
	}
	
	public function importarProdutos($produtos) {
		$arrCategorias = array();
		$arrRetorno = array();
		$arrUpload_dir = wp_upload_dir();
		$upload_dir = $arrUpload_dir['basedir'].'/'.date('Y/m').'/';
		$upload_url = $arrUpload_dir['baseurl'].'/';			
		
		if (!$this->validaJson($produtos)) {
			return array('sucesso' => 0, 'msgRetorno'=> "JSON INVALIDO");
		}		

		$arrProdutos = json_decode($produtos, true);
		foreach ($arrProdutos as $produto) {			
			$query = array(
				'author'   => 1,
				'post_type'   => 'imoveis',		
				'meta_key'   					=> 'codigo_do_imovel',
				'meta_value'					=> $produto['dados_gerais']['id_original'],
				'post_status' => 
					array(
						'publish',
						'pending',
						'draft',
						'auto-draft',
						'future',
						'private',
						'inherit',
						'trash'
					)
			);	
			
			
			$produtos = new WP_Query($query);
			
			$novo_produto = true;
			if ($produtos->have_posts() == false) {
				$post = array(
					'post_author' => 1,
					'post_status' => "draft",
					'post_title' => wp_strip_all_tags($produto['dados_gerais']['nome']),
					'post_content' => $produto['dados_gerais']['descricao_completa'],
					'post_type' => "imoveis"
				);				

				$product_id = $post_id = wp_insert_post( $post);
			} else {
				$product_id = $produtos->post->ID;
				$post = array(
					'ID' => $product_id,
					'post_author' => 1,
					// 'post_status' => $produto['dados_gerais']['exibir'] == 'SIM' ? 'publish' : "draft",
					'post_status' => "draft",
					'post_title' => wp_strip_all_tags($produto['dados_gerais']['nome']),
					'post_parent' => '',
					'post_content' => $produto['dados_gerais']['descricao_completa'],
					'post_type' => "imoveis",
					'comment_status' => "open"
				);					
				$post_id = wp_update_post( $post );
				
				$novo_produto = false; 
			}
			
			update_post_meta( $post_id, 'codigo_do_imovel', $produto['dados_gerais']['id_original']);			
			update_field('codigo_do_imovel', $produto['dados_gerais']['id_original'], $post_id);
			
			$valor_imovel = $produto['dados_gerais']['valor_venda'] != '' ? $produto['dados_gerais']['valor_venda'] : $produto['dados_gerais']['valor_locacao'];
			update_post_meta( $post_id, 'valor-do-imovel', $valor_imovel);
			update_field('valor-do-imovel', $valor_imovel, $post_id);
			

			$valor_total = '';
			if ($produto['dados_gerais']['valor_locacao'] != '' && $produto['dados_gerais']['status'] == 'ALUGUEL') {
			   $valor_total = $produto['dados_gerais']['valor_locacao'] + $produto['dados_gerais']['valor_iptu'] + $produto['dados_gerais']['valor_condominio'];							
			}
			
			update_post_meta( $post_id, 'total', $valor_total);
			update_field('total', $valor_total, $post_id);
			
			update_post_meta( $post_id, 'valor-do-condominio', $produto['dados_gerais']['valor_condominio']);
			update_field('valor-do-condominio', $produto['dados_gerais']['valor_condominio'], $post_id);
			
			update_post_meta( $post_id, 'iptu', $produto['dados_gerais']['valor_iptu']);
			update_field('iptu', $produto['dados_gerais']['valor_iptu'] > 0 ? $produto['dados_gerais']['valor_iptu'] : '0' , $post_id);
			
			update_post_meta( $post_id, 'quartos', $produto['dados_gerais']['quantidade_quartos']);
			update_field('quartos', $produto['dados_gerais']['quantidade_quartos'], $post_id);
			
			update_post_meta( $post_id, 'banheiros', $produto['dados_gerais']['quantidade_banheiros']);
			update_field('banheiros', $produto['dados_gerais']['quantidade_banheiros'], $post_id);
			
			update_post_meta( $post_id, 'vagas_de_garagen', $produto['dados_gerais']['quantidade_vagas']);
			update_field('vagas_de_garagen', $produto['dados_gerais']['quantidade_vagas'], $post_id);
			
			update_post_meta( $post_id, 'metragem', $produto['dados_gerais']['metragem']);
			update_field('metragem', $produto['dados_gerais']['metragem'], $post_id);
			
			update_post_meta( $post_id, 'nome_do_condominio', $produto['dados_gerais']['nome_condominio']);
			update_field('nome_do_condominio', $produto['dados_gerais']['nome_condominio'], $post_id);
			
			update_post_meta( $post_id, 'bairro', $produto['dados_gerais']['bairro']);
			update_field('bairro', $produto['dados_gerais']['bairro'], $post_id);
			
			update_post_meta( $post_id, 'endereco', $produto['dados_gerais']['endereco']);
			update_field('endereco', $produto['dados_gerais']['endereco'], $post_id);
			
			update_post_meta( $post_id, 'descricao', $produto['dados_gerais']['descricao_completa']);
			update_field('descricao', $produto['dados_gerais']['descricao_completa'], $post_id);
			
			$integracao_vista_campos_infra_carac = get_option('integracao_vista_campos_infra_carac');
			$arrInfraCustomizado = json_decode($integracao_vista_campos_infra_carac, true);
			
			if (!empty($produto['infraestrutura'])) {
				$arrInfra = [];
				foreach ($produto['infraestrutura'] as $key => $value) {
					if ($value == 'Sim') {
						if (isset($arrInfraCustomizado[$key])) {
							$arrInfra[] = $arrInfraCustomizado[$key];
						} else {
							$arrInfra[] = $key;
						}
					}
				}
				update_post_meta( $post_id, 'comodidades_do_condominio', serialize($arrInfra));			
				update_field( 'comodidades_do_condominio', $arrInfra, $post_id );	
			}
			
			$endereco_completo = sprintf(
				'%s %s, %s, %s, %s',
				'99',
				$produto['dados_gerais']['endereco'],
				$produto['dados_gerais']['cidade'],
				'Brazil',
				$produto['dados_gerais']['cep']				
			);
			$localizacao = array('address' => $endereco_completo, 'lat' => $produto['dados_gerais']['latitude'], 'lng' => $produto['dados_gerais']['longitude']);        
			update_field('google_maps', $localizacao, $post_id);			
			
			wp_set_object_terms($post_id, strtolower($produto['dados_gerais']['status']), 'status_do_imovel');
			wp_set_object_terms($post_id, strtolower($produto['dados_gerais']['tipo']), 'tipos_de_imoveis');
			
			$arrMetragensMedia = [
				array(0, 100, 'ate100m'),
				array(100, 200, 'entre100e200m'),
				array(200, 300, 'entre200e300m',),
				array(300, 400, 'entre300e400m'),
				array(400, 999999, 'maisde400m'),
			];
			
			$arrTermos = array();
			$terms = get_terms( 'metragem_media', array(
				'hide_empty' => false,
			) );			
			foreach ($terms as $termo) {
				$slug = $termo->slug;
				$arrTermos[urldecode($slug)] = $termo->term_id;
			}
			$metragem_imovel = $produto['dados_gerais']['metragem'];
			foreach ($arrMetragensMedia as $key => $metragem_media) {
				if ($metragem_imovel > $metragem_media[0] && $metragem_imovel < $metragem_media[1]) {
					wp_set_object_terms($post_id, $arrTermos[$metragem_media[2]], 'metragem_media');
					break;
				}
			}
			
			
			$arrImagensImovel = get_field('galeria', $post_id, false);
			if (!is_array($arrImagensImovel )) {
				$arrImagensImovel = [];
			}
			
			if (isset($produto['imagens']) && count($produto['imagens']) > 0) {
				$aux_imagem = 0;
				foreach ($produto['imagens'] as $produto_imagem) {
						// $nome_arquivo = $upload_dir.$post_id.$aux_imagem.'.jpg';
						$nome_arquivo = $upload_dir.$produto_imagem['Codigo'].$aux_imagem.'.jpg';
					
						$imagem = $this->downloadImagem($produto_imagem['Foto']);					
						if ($imagem['status'] != '404') {
								$wp_upload_dir = wp_upload_dir();
								$imagem_id = $this->pippin_get_image_id( $wp_upload_dir['url'].'/'.basename($nome_arquivo) );
								if ($imagem_id == 0 || (md5_file($nome_arquivo) !== md5($imagem['conteudo'])) ) {										
										$arquivo = fopen($nome_arquivo,'w');
										fwrite($arquivo,$imagem['conteudo']);
										fclose($arquivo);
										$filetype = wp_check_filetype(basename($nome_arquivo), null);
										$attachment = array(
												'guid' => $wp_upload_dir['url'].'/'.basename($nome_arquivo),
												'post_mime_type' => $filetype['type'],
												'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $nome_arquivo ) ),
												'post_content' => '',
												'post_status' => 'inherit'
										);
										$attach_id = wp_insert_attachment($attachment,$nome_arquivo,$post_id);
										require_once( ABSPATH . 'wp-admin/includes/image.php' );
										$attach_data = wp_generate_attachment_metadata( $attach_id, $nome_arquivo );
										wp_update_attachment_metadata( $attach_id, $attach_data );
										
										$imagem_id = $attach_id;
								}
							
								$arrImagensImovel[] = $imagem_id;
								if ($produto_imagem['Destaque'] == "Sim") {
									update_post_meta( $post_id, '_thumbnail_id', $imagem_id );
								}
						}
						$aux_imagem++;
				}
				update_field('galeria', $arrImagensImovel, $post_id);
			}
			
			
			$msg_retorno = sprintf('Produto %s - %s %s com sucesso', $produto['dados_gerais']['id_original'], $produto['dados_gerais']['nome'], $novo_produto === false ? 'atualizado' : 'cadastrado');
			$arrRetorno[] = ['msg' => $msg_retorno, 'codigo_wordpress' => $product_id];
		}
		
		return $arrRetorno;
	}

	function pippin_get_image_id($image_url) {
		global $wpdb;
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url )); 
		if (isset($attachment[0])) {
			return (int) $attachment[0]; 
		}
		return 0;
	}
	
	function downloadImagem($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);		
		$imagem = curl_exec($ch);
		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if (curl_error($ch)) {
			$error_msg = curl_error($ch);
		}
		
		if (isset($error_msg)) {
			print "Erro no curl";
			print $error_msg;
			exit;
		}
		
		curl_close($ch);	
		return ['status' => $httpcode, 'conteudo' => $imagem];
	}
	
	function callCurl($url, $post = [], $metodo = 'POST', $desabilitaCabecalho = FALSE) {
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 5000);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
		if ($metodo == 'POST') {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
		if (!$desabilitaCabecalho) {
			curl_setopt(
				$curl,
				CURLOPT_HTTPHEADER,
				array(
					"Content-Type: application/json",
					"Accept: application/json",
				)
			);
		}
		
		$response = curl_exec($curl);
		
		
		if (curl_error($curl)) {
			$error_msg = curl_error($curl);
		}		
		
		if (isset($error_msg)) {
			print "Erro no curl";
			print $error_msg;
			exit;
		}
		
		curl_close($curl);
		
		
		return $response;
	}	
	
	function validaJson($json){
	   return is_string($json) && is_array(json_decode($json, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
	}
}
	
