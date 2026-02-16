<?php include 'partials/header.php'; ?>

<div class="container">
    <h1>Consulta de Certificaciones por Cédula</h1>
    <form id="queryForm">
        <input type="text" id="cedula" name="cedula" required pattern="\d{10}" title="Cédula de 10 dígitos" placeholder="Ingrese su Cédula">
        <button type="submit">Consultar</button>
    </form>
    <div id="results"></div>
</div>

<script src="js/main.js"></script>

<?php include 'partials/footer.php'; ?>