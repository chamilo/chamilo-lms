<div id="centrado">
  <div id="mainContentFrame">
    <!--img id="logo"/-->
    <div class="border">
        <div>
            <h3 class="titulo" id="title"></h3>
            <span id="subtitle"></span>
        </div>

		<div id="statusContainer">
			<div id="resultMessage">
				<h4 class="result-title"></h4>
				<div class="result-message"></div>
			</div>
		
			<div id="testInProgress">
				<img src="loading.gif" width="16" height="16" align="absmiddle"/> <span id="testInProgressLabel"></span>
			</div>
		</div>

		<div id="progressPanel">
			<div id="progressbar"></div>
		</div>

		<div id="buttonPanel"> 
            <form>
                <input type="button" id="startTestButton" class="btn"/>
            </form>
        </div>
    </div>
    </div>
	<div class="resultado_datos" id="technicalDetails">
		Celeridad de descarga:<br />
                 &nbsp;&nbsp;Efectivo: <span id="currentdlspeed"></span><br />
                 &nbsp;&nbsp;Mínimo requerido: <span id="requireddlspeed" class="required"></span>
		<br/>
		Celeridad de subida:<br />
                 &nbsp;&nbsp; Efectivo : <span id="currentulspeed"></span><br />
		 &nbsp;&nbsp; Mínimo requerido: <span id="requiredulspeed" class="required"></span>
		<br/>
                Tiempo de respuesta:<br />
                &nbsp;&nbsp;Efectivo: <span id="currenttime"></span><br />
                &nbsp;&nbsp;Máximo requerido: <span id="requiredtime" class="required"></span>
        <br/>
</div>
</div>
