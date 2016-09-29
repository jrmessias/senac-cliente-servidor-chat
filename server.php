<?php
require_once("conexao.php");

$servidor = '127.0.0.1'; // host
$porta = '9000'; // porta
$tempoLimite = 10 * 60; // minutos * segundos
$null = null;

/* Definindo padrões do script */
date_default_timezone_set('America/Sao_Paulo');
set_time_limit(0);
ini_set("default_socket_timeout", $tempoLimite);
error_reporting(0);

// Criando um Stream Socket TCP/IP
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// Reutilizando porta
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Aplicando socket ao host
socket_bind($socket, 0, $porta);

// Abrindo escuta de porta
socket_listen($socket);

// Criando e adicionando escuta do socket para a lista
$clientes = array($socket);

// Iniciar loop infinito, este script não para
while (true) {
    // Gerenciando múltiplas conexões
    $modificado = $clientes;
    // Retorna os recursos de socket em ordem de ocorrencia
    socket_select($modificado, $null, $null, 0, 10);

    // Verifica por um novo socket
    if (in_array($socket, $modificado)) {
        $socketNovo = socket_accept($socket); //Aceita novo socket
        $clientes[] = $socketNovo; //Adiciona o socket ao array de cliente

        $cabecalho = socket_read($socketNovo, 1024); // Le os dados enviados pelo socket
        confirmaWebsocket($cabecalho, $socketNovo, $servidor, $porta); // Executa conexão do websocket

        socket_getpeername($socketNovo, $ip); // Pega o ip do socket conectado
        $resposta = mask(json_encode(array(
                'type' => 'system',
                'message' => $ip . ' conectou em ' . date('d/m/Y') . ' às ' . date('H:i:s'))
        )); //Converte dados para json
        sendMessage($resposta); // Notifica os usuário sobre a nova conexão

        //Abre espaço para um novo socket
        $socketEncontrado = array_search($socket, $modificado);
        unset($modificado[$socketEncontrado]);
    }

    //Loop através de todos sockets
    foreach ($modificado as $socketModificado) {
        //Checando qualquer dado enviado
        while (socket_recv($socketModificado, $buffer, 1024, 0) >= 1) {
            $textoRecebido = unMask($buffer); //unMask dado
            $jsonMensagem = json_decode($textoRecebido); //Decodifica JSON - String => JSON
            $nomeDoUsuario = $jsonMensagem->name; //Nome
            $mensagemDoUsuario = $jsonMensagem->message; //Texto
            $corDoUsuario = @$jsonMensagem->color; //Cor

            if ($nomeDoUsuario == null || $mensagemDoUsuario == null) {
                break 2;
            }

            //Prepara os dados para serem enviados para o cliente
            $mensagemArray = array(
                'type' => 'usermsg',
                'name' => $nomeDoUsuario,
                'message' => filtraPalavras($mensagemDoUsuario),
                'color' => $corDoUsuario
            );
            $textoDaResposta = mask(json_encode($mensagemArray));
            sendMessage($textoDaResposta); //Envia os dados
            salvaMensagem($mensagemArray); // Salva no bd

            break 2; //Sai do loop
        }

        $buffer = @socket_read($socketModificado, 1024, PHP_NORMAL_READ);
        if ($buffer === false) { // Checa se o cliente se desconectou
            // Remove cliente do array $clientes
            $socketEncontrado = array_search($socketModificado, $clientes);
            socket_getpeername($socketModificado, $ip);
            unset($clientes[$socketEncontrado]);

            //Notifica todos usuários sobre a desconexão
            $resposta = mask(json_encode(array(
                'type' => 'system',
                'message' => $ip . ' desconectou em ' . date('d/m/Y') . ' às ' . date('H:i:s')
            )));
            sendMessage($resposta);
        }
    }
}
// Fecha a escuta do socket
socket_close($socket);

// Efetua o envio da mensagem para os clientes
function sendMessage($mensagem)
{
    global $clientes;
    foreach ($clientes as $socketModificado) {
        @socket_write($socketModificado, $mensagem, strlen($mensagem));
    }
    return true;
}

//Desmascara mensagem de chegada (quebrada)
function unMask($texto)
{
    $comprimento = ord($texto[1]) & 127;
    if ($comprimento == 126) {
        $mascaras = substr($texto, 4, 4);
        $dado = substr($texto, 8);
    } elseif ($comprimento == 127) {
        $mascaras = substr($texto, 10, 4);
        $dado = substr($texto, 14);
    } else {
        $mascaras = substr($texto, 2, 4);
        $dado = substr($texto, 6);
    }
    $texto = "";
    for ($i = 0; $i < strlen($dado); ++$i) {
        $texto .= $dado[$i] ^ $mascaras[$i % 4];
    }
    return $texto;
}

//Codifica mensagem para transferir ao cliente
function mask($texto)
{
    $b1 = 0x80 | (0x1 & 0x0f);
    $comprimento = strlen($texto);

    if ($comprimento <= 125)
        $cabecalho = pack('CC', $b1, $comprimento);
    elseif ($comprimento > 125 && $comprimento < 65536)
        $cabecalho = pack('CCn', $b1, 126, $comprimento);
    elseif ($comprimento >= 65536)
        $cabecalho = pack('CCNN', $b1, 127, $comprimento);
    return $cabecalho . $texto;
}

//Executa conexão de novo socket
function confirmaWebsocket($cabecalhoRecebido, $conexaoDoCliente, $servidor, $porta)
{
    $cabecalhos = array();
    $linhas = preg_split("/\r\n/", $cabecalhoRecebido);
    foreach ($linhas as $linha) {
        $linha = chop($linha);
        if (preg_match('/\A(\S+): (.*)\z/', $linha, $combina)) {
            $cabecalhos[$combina[1]] = $combina[2];
        }
    }

    $chaveDeSeguranca = $cabecalhos['Sec-WebSocket-Key'];
    $aceiteDeSeguranca = base64_encode(pack('H*', sha1($chaveDeSeguranca . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    //HandShaking Header
    $cabecalho = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: $servidor\r\n" .
        "WebSocket-Location: ws://$servidor:$porta\r\n" .
        "Sec-WebSocket-Accept:$aceiteDeSeguranca\r\n\r\n";
    socket_write($conexaoDoCliente, $cabecalho, strlen($cabecalho));
}

// Filtro de palavrões
function filtraPalavras($mensagem)
{
    $palavroes = ['cu', 'filho da puta', 'cabeção', 'merda', 'arrombado'];
    foreach ($palavroes as $palavrao) {
        $mensagem = str_ireplace($palavrao, ':exclamation:', $mensagem);
    }
    return $mensagem;
}

// Salva Mensage
function salvaMensagem($mensagem)
{
    global $link;

    $sql = "INSERT INTO chat (cor, nome, mensagem, datahora) VALUES ('" . $mensagem['color'] .
        "' , '" . $mensagem['name']  .
        "' , '" . mysqli_escape_string($link, $mensagem['message']) .
        "' , '" . date('Y-m-d H:i:s') . "')";
    //sendMessage($sql);
    mysqli_query($link, $sql);

}