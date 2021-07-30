<?php
/*
Plugin Name: Customizações - Integração Vista
Description: Integração com o sistema Vista para importação de imóveis
Version: 1.0
License: GPL
*/

/* PAINEL DE SINCRONIZAÇÃO */
add_action('admin_menu', 'integracao_vista_create_menu');

function integracao_vista_create_menu() {

	//create new top-level menu
	add_menu_page('Sincronização Integração Vista', 'Sincronização Integração Vista', 'administrator', 'integracao-vista-main', 'integracao_vista_plugin_settings_page' , 'dashicons-share-alt' );
	
	add_submenu_page( 'integracao-vista-main', 'Configurações', 'Configurações',
    'administrator', 'integracao-vista-configuracoes', 'integracao_vista_configuracoes');
	
	add_submenu_page( 'integracao-vista-main', 'Sincronização Manual', 'Sincronização Manual',
    'administrator', 'integracao-vista-sincronizacao-manual', 'integracao_vista_plugin_settings_page');	

	//call register settings function
	add_action( 'admin_init', 'register_integracao_vista_plugin_settings' );
	
	remove_submenu_page('integracao-vista-main','integracao-vista-main');
}


function register_integracao_vista_plugin_settings() {
	register_setting( 'integracao-vista-settings-group', 'integracao_vista_chave_vista' );
	register_setting( 'integracao-vista-settings-group', 'integracao_vista_campos_infra_carac' );
}

function integracao_vista_configuracoes()
{
   
?>
<div class="wrap">
<h1>Configurações</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'integracao-vista-settings-group' ); ?>
    <?php do_settings_sections( 'integracao-vista-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Chave de acesso ao vista</th>
        <td><input type="text" style="width:50%" name="integracao_vista_chave_vista" value="<?php echo esc_attr( get_option('integracao_vista_chave_vista') ); ?>" /></td>
        </tr>
		
        <tr valign="top">
        <th scope="row">Campos - Infraestrutura/Características</th>
        <td><textarea name="integracao_vista_campos_infra_carac" cols="100" rows="20" /><?php echo esc_attr( get_option('integracao_vista_campos_infra_carac') ); ?></textarea></td>
        </tr>		
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php
}

function integracao_vista_plugin_settings_page() {
	
	$caminho_plugin = WP_PLUGIN_DIR . '/integracao-vista';
	
	$url_plugin = plugin_dir_url( __FILE__ );
?>
<div class="wrap">
<h1>Sincronização de Imóveis - Integração Vista</h1>
<p> Para criar um agendamento no servidor, siga as intruções: </p>
<ul class="instrucoes">
	<li> Acesse o Cpanel da sua hospedagem (Consulte seu provedor com orientações)</li>
	<li> Após logado, vá em Avançado > Tarefas Cron </li>
	<li>Em <strong>Configurações comuns</strong>, selecione umas das opções disponíveis (Sugestão <i>Uma vez por hora</i>)</li>
	<li>Em <strong>Comando</strong> coloque: <br>
	 <input type="text" style="width:80%;" readonly value="/usr/local/bin/php <?php echo ABSPATH . 'wp-content/plugins/integracao-vista/importacao.php' ?>">
	</li>
	<li> Clique em <strong>Adicionar nova tarefa cron e pronto, a tarefa será executada sempre no horário estabelecido</strong></li>
	<li> Se quiser ser notificado por e-mail toda vez que a tarefa for executada,
	     você pode no inicio da tela de configuração do agendamento,
	     inserir um endereço de e-mail.
	</li>
</ul>
<style>
#progress {
  width: 100%;
  background-color: #ddd;
}

#barraProgresso {
  width: 1%;
  height: 30px;
  background-color: #04AA6D;
  color: #fff;
}

ul.instrucoes {
	list-style-type: square;
	margin-left:20px;
}
</style>

<form method="post" action="options.php">
    <?php settings_fields( 'integracao-vista-settings-group' ); ?>
    <?php do_settings_sections( 'integracao-vista-settings-group' ); ?>
    <table class="form-table">
		<?php 
			$sincronizacao_em_andamento = file_exists($caminho_plugin.'status.json');
			if (!$sincronizacao_em_andamento) {
		?>	
		
        <tr valign="top">
        <th scope="row">Iniciar sincronização manual</th>
        <td><input type="button" name="iniciaSincronizacao" id="iniciaSincronizacao" value="Iniciar sincronização" /></td>
        </tr>
        <tr>
			<td colspan="2">O processo de sincronização pode levar vários minutos, por favor seja paciente ;)</td>
        </tr>
        <?php } else { ?>
        <tr>
			<td colspan="2">Desculpe, já existe outra sincronização em andamento!</td>
        </tr>        
        <?php } ?>
    </table>
	<div class="progress" style="display:none;">
		<div class="progress-bar" id="barraProgresso" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="">
			0%
		</div>
	</div>    
</form>
</div>
<script type="text/javascript">
	function sincronizacaoManual() {
		jQuery.ajax({
			url: "<?php echo $url_plugin;?>importacao.php",
			type: "GET",
			data: "sincronizacao_manual=1",
			datatype: "json",
			complete: function() {
				console.log('Importacao Finalizada');
			}
		});
		t = setTimeout("updateStatus()", 3000);
	}
	
	function updateStatus() {  
		jQuery.getJSON('<?php echo $url_plugin;?>status.json', function(data){ 
			var items = [];
			pbvalue = 0;
			if(data){
				var total = data['total'];
				var current = data['current']; 
				var pbvalue = Math.floor((current / total) * 100); 
				
				console.log('pbvalue: ' + pbvalue);
				if(pbvalue>0){ 
				
					document.getElementById("barraProgresso").setAttribute("aria-valuenow", "50%");
					
					document.getElementById("barraProgresso").style.width = pbvalue + "%";

					document.getElementById("barraProgresso").innerHTML = pbvalue + "%";
					
					if (pbvalue >= 100) {
					 document.getElementById("barraProgresso").classList.add("progress-bar-success");
					}
				} 
			} 

			if(pbvalue < 100){ 
				t = setTimeout("updateStatus()", 3000); 
			} 
		});   
	}
	
	jQuery('#iniciaSincronizacao').click( function() {
		jQuery('#iniciaSincronizacao').prop('disabled', true);
		jQuery('.progress').show();
		sincronizacaoManual();
	});
</script>
<?php } ?>