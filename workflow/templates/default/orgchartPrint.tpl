<html>
<head>
</head>
<body>
{$header}
{$css}
{$javaScripts}

<div class="invisiblePrint">
	<h1>Configurar Impress�o</h1>
	<label><input type="checkbox" checked id="employeesVisibility" /> Exibir funcion�rios</label>
	<br/>
	<label><input type="checkbox" id="photoVisibility" /> Exibir foto</label>
	<br/>
	<label><input type="checkbox" id="mailVisibility" /> Exibir e-mail</label>
	<br/>
	<label><input type="checkbox" id="categoryVisibility" /> Exibir v�nculo</label>
	<br/>
	<label><input type="checkbox" checked id="groupByArea" /> Agrupar por �rea</label>
	<br/>
	<label><input type="checkbox" checked id="highlightSupervisor" /> Ressaltar titular</label>
	<br/>
	<label><input type="checkbox" checked id="orgchartPathVisibility" /> Exibir "caminho completo" da �rea</label>
	<br/><br/>
	<button id="printButton">Imprimir</button>
</div>


{if !empty($organizationName)}
<h1 class="organizationName">{$organizationName}</h1>
{/if}

<div id="areas_content" />

{$footer}
</body>
</html>
<script language="javascript">
var areas = {$areasJson};
var authorized = {$authorized};
var authorized_matricula = {$authorized_matricula};
var authorized_cargo = {$authorized_cargo};
var authorized_funcao = {$authorized_funcao};
var authorized_data_admissao = {$authorized_data_admissao};
</script>
