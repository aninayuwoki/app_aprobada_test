<?php include 'partials/header.php'; ?>

<div class="container">
    <h1>Consulta de Certificados por Cédula</h1>
    <form id="certificadosForm">
        <input type="text" id="cedulaCert" name="cedula" required pattern="\d{10}" title="Cédula de 10 dígitos" placeholder="Ingrese su Cédula">
        <button type="submit">Consultar Certificados</button>
    </form>
    <div id="resultsCert"></div>
</div>

<script src="js/certificados.js"></script>

<?php include 'partials/footer.php'; ?>