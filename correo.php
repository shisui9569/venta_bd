<?php
function enviarCorreo($para, $numero_pedido, $productos, $total) {
    $mensaje = "
    <html>
    <body>
        <h1>Confirmación de Pedido</h1>
        <p>Tu pedido #$numero_pedido fue procesado con éxito.</p>
        <p><strong>Productos:</strong> $productos</p>
        <p><strong>Total:</strong> S/. $total</p>
    </body>
    </html>";

    $asunto = "Confirmación de Pedido - $numero_pedido";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: no-reply@tuempresa.com\r\n";

    return mail($para, $asunto, $mensaje, $headers);
}
?>
