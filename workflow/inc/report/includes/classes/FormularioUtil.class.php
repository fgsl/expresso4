<?php
/**
 * FormularioUtil
 * 
 * Funções uteis que podem ser usadas para facilitar e padronizar o desenvolvimento.
 * @author Jair Pereira <pereira.jair@gmail.com>
 */
class FormularioUtil {
	

   /**
     * Inclui os arquivos de CSS e de Javascript na P�gina.
     * 
     * @return void
     */
   function incluiCssJavascript($addpath = "") {
    	echo "\n\n<script language=\"Javascript\" type=\"text/javascript\" src=\"$addpath./includes/js/mascaras.js\"></script>\n
<script language=\"Javascript\" type=\"text/javascript\" src=\"$addpath./includes/js/jquery-1.3.2.js\"></script>\n
<script language=\"Javascript\" type=\"text/javascript\" src=\"$addpath./includes/js/validacoes.js\"></script>\n
<script language=\"Javascript\" type=\"text/javascript\" src=\"$addpath./includes/js/auxiliares.js\"></script>\n
<script language=\"Javascript\" type=\"text/javascript\" src=\"$addpath./includes/js/calendar.js\"></script>\n
<script language=\"Javascript\" type=\"text/javascript\" src=\"$addpath./includes/js/FormularioUtil.js\"></script>\n
<link rel=\"stylesheet\" href=\"$addpath./includes/css/base_form.css\" media=\"screen\"></link>\n
<link rel=\"stylesheet\" href=\"$addpath./includes/css/calendar.css\" media=\"screen\"></link>\n\n";
    	
    }
   
    /**
     * Função para escrever uma Mensagem de Erro ou Sucesso na Listagem.
     * @param $msg
     * @return string unknown_type
     */
    function escreveMensagem($msg) {
        if ($msg) {
          echo "<div class='msg'>$msg<br><br></div>";
        } else {
            echo "<br>";
        }
    }
   
   /**
     * Abre o Quadro Principal
     * 
     * Fun��o para abrir o quadro Principal do Sistema.
     *
     * @param  string $titulo
     * @return void
     */
   function abreQuadro($titulo) {
   	 $html = '<br><div align=center><table width="98%" class="tableMoldura">
<tr class="tableTitulo">
    <td><h1>' . $titulo . '</h1></td>
</tr>
<tr>
    <td align="center"><br>
    
    <table width="98%">';
    
    echo $html;
   }
   
   /**
     * Fecha o Quadro Principal
     * 
     * Fun��o para fechar o quadro Principal do Sistema.
     * 
     * @return void
     */
   function fechaQuadro() {
   	  	$html = "\n</td></tr>\n</table>\n</div>";
    	echo $html;
   }
   
   /**
     * MontaArraySelect
     * 
     * Fun��o para Retornar o array necess�rio para montar um campo do tipo SELECT. 
     * Par�metros: Conex�o, SQL, Id (nome do campo que ser� usado para o value das options), Valor (nome do campo que ser� mostrado nas options) 
     *
     * @param  string $conn
     * @param  string $sql
     * @param  string $id
     * @param  string $value
     * @return array $arrSelectValues
     */
   function MontaArraySelect($conn,$sql,$id,$value) {
	    $arrSelectValues = array();
		$resu = pg_query($conn,$sql);
		if (pg_num_rows($resu)>0) {
	        for ($x=0;$x<pg_num_rows($resu);++$x)  {
	            $arrSelectValues[pg_fetch_result($resu,$x,$id)] = pg_fetch_result($resu,$x,$value);
	        }
		}
		return $arrSelectValues;
    }
    
    /**
     * MontaArraySelect
     * 
     * Fun��o para Retornar o array necess�rio para montar um campo do tipo SELECT. 
     * Par�metros: Conex�o, SQL, Id (nome do campo que ser� usado para o value das options), Valor (nome do campo que ser� mostrado nas options) 
     *
     * @param  string $conn
     * @param  string $sql
     * @param  string $id
     * @param  string $value
     * @return array $arrSelectValues
     */
   function MontaArrayCheckBox($conn,$sql,$oid,$descricao) {
	    $arrSelectChecks = array();
		$resu = pg_query($conn,$sql);	
		if (pg_num_rows($resu)>0) {
	        for ($x=0;$x<pg_num_rows($resu);++$x)  {
	            $arrSelectChecks[] = array(pg_fetch_result($resu,$x,$oid), pg_fetch_result($resu,$x,$descricao), false);
	        }
		}
		return $arrSelectChecks;
    }
    
    function MontaArrayRadio($conn,$sql,$oid,$descricao) {
	    $arrSelectChecks = array();
		$resu = pg_query($conn,$sql);	
		if (pg_num_rows($resu)>0) {
	        for ($x=0;$x<pg_num_rows($resu);++$x)  {
	            $arrSelectChecks[] = array(pg_fetch_result($resu,$x,$oid), pg_fetch_result($resu,$x,$descricao), false);
	        }
		}
		return $arrSelectChecks;
    }
    
    function MontaArrayCondicao($conn,$sql,$key,$condicao) {
    	$arr = array();
		$resu = pg_query($conn,$sql);	
		if (pg_num_rows($resu)>0) {
			while ($linha = pg_fetch_array($resu,$i,PGSQL_ASSOC)) {
				$ret = $this->validaCondicao($condicao,$linha);
				if ($ret) {
					array_push($arr,$linha[$key]);
				}
			}
		}
		return $arr;
    }
    
    public function validaCondicao($condicao,$linha,$debug = false) {
        foreach (array_keys($linha) as $col) {
            $condicao = str_replace("{" .  $col . "}","'" . $linha[$col] . "'",$condicao);
        }
        $fcondicao = ' if (' . $condicao . ') { $ret = true; } else { $ret = false; }';
        if ($condicao != "") {
          if ($debug) { echo "<br>" . $fcondicao ; } 
          eval($fcondicao);
        } else {
            $ret = false;
        }
        return $ret;
    }
	
}

?>