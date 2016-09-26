var stringToColor = function (str) {

    // str to hash
    for (var i = 0, hash = 0; i < str.length; hash = str.charCodeAt(i++) + ((hash << 5) - hash));

    // int/hash to hex
    for (var i = 0, colour = "#"; i < 3; colour += ("00" + ((hash >> i++ * 8) & 0xFF).toString(16)).slice(-2));

    return colour;
};

var scrollDown = function (target) {
    target.animate({scrollTop: target.height()}, 0);
};

$(document).ready(function () {

    var messageBox = $("#box-message");

    // Criação de um novo websocket
    var wsUri = "ws://172.16.2.128:9000/server.php";
    websocket = new WebSocket(wsUri);

    websocket.onopen = function (e) { // Ao abrir a conexão
        messageBox.append("<div class=\"system-success\">Conectado!</div>"); // Notificar usuário
        console.log("Conectado!"); // Notificar usuário
    }

    $('#btn-send').click(function (e) { // Ao clicar o botão
        e.preventDefault();

        var name = $("#nome").val();
        var message = $("#mensagem").val();

        // Nome
        if (name == "") {
            alert("Digite seu nome!");
            $("#nome").focus();
            return;
        }

        // Mensagem
        if (message == "") {
            alert("Digite sua mensagem!");
            $("#mensagem").focus();
            return;
        }

        // Prepara os dados em JSON
        var msg = {
            message: message,
            name: name,
            color: stringToColor(name)
        };

        // Converte os dados para JSON e envia
        websocket.send(JSON.stringify(msg));

        // Limpa mensagem
        $('#mensagem').val('');
    });

    // Mensagem recebida do servidor
    websocket.onmessage = function (e) {
        var msg = JSON.parse(e.data); // Mensagem enviado pelo servidor PHP
        var type = msg.type; // Tipo da mensagem
        var userMessage = msg.message; // Texto da mensagem
        var userName = msg.name; // Nome do usuário
        var userColor = msg.color; // Cor

        if (type == 'usermsg') {
            // Junior : Mensagem
            messageBox.append("<div><span class=\"user-name\" style=\"color:" + userColor + "\">" + userName + "</span>: <span class=\"user-message\">" + userMessage + "</span></div>");
        }

        if (type == 'system') {
            messageBox.append("<div class=\"system-msg\">" + userMessage + "</div>");
        }

        scrollDown(messageBox);

        $('.user-message').each(function(i, d){
            $(d).emoji();
        });
    };

    websocket.onerror = function (ev) {
        messageBox.append("<div class=\"system-error\">Ocorreu um erro - " + ev.data + "</div>");
    };
    websocket.onclose = function (ev) {
        messageBox.append("<div class=\"system-msg\">Conexão fechada.</div>");
    };
});// read