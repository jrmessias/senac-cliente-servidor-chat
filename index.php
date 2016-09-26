<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Websocket Chat">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">
    <title>Websocket Chat</title>
    <!-- Bootstrap core CSS -->
    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="bower_components/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="css/chat.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="jumbotron">
            <h1><i class="fa fa-comments-o"></i> Chat PHP Websocket</h1>
            <p class="lead">Chat utilizando websockets com PHP.</p>
        </div>
        <div class="row marketing">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-comments-o"></i> Chat</h3>
                    </div>
                    <div class="panel-body">
                        <div class="box-message" id="box-message">
                        </div>
                    </div>
                    <div class="panel-footer">
                        <form class="form-inline">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome">
                            </div>
                            <div class="form-group">
                                <label for="mensagem">Mensagem</label>
                                <input type="text" maxlength="255" class="form-control" id="mensagem"
                                       placeholder="Mensagem">
                            </div>
                            <button class="btn btn-success" id="btn-send">Enviar <i class="fa fa-send"></i></button>
                            <a class="btn btn-default" href="http://www.webpagefx.com/tools/emoji-cheat-sheet/" target="_blank"><i class="fa fa-smile-o"></i></a>
                            <div class="form-group">
                                <label for="websocket">Websocket</label>
                                <input type="text" maxlength="255" class="form-control" id="websocket" value="ws://<?php echo $_SERVER['REMOTE_ADDR']; ?>:9000"
                                       placeholder="Websocket">
                            </div>
                            <button class="btn btn-primary" id="btn-connect">Conectar <i class="fa fa-sign-in"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer">
            <p>&copy; 2016.</p>
        </footer>
    </div> <!-- /container -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="bower_components/jquery-emoji/jquery.emoji.js"></script>
    <script src="js/chat.js"></script>
</body>
</html>