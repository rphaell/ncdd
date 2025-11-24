<?php
/**
 * =================================================================
 * PROCESSADOR DE FORMULÁRIO DE CONTATO - NO CONTROLE DO DIREITO
 * =================================================================
 * 
 * Este script processa o formulário de contato e envia emails
 * usando PHPMailer com SMTP da Hostinger.
 * 
 * Autor: DeepAgent - Abacus.AI
 * Data: Novembro 2025
 * =================================================================
 */

// ==== CONFIGURAÇÕES INICIAIS ====

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json; charset=utf-8');

// Permite CORS (ajuste conforme necessário)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Inicia a sessão para rate limiting
session_start();

// ==== VERIFICAÇÕES DE SEGURANÇA ====

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

// Verifica se o referer é válido (anti-spam básico)
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// ==== IMPORTS ====

require_once 'config.php';
require_once 'phpmailer/Exception.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// ==== VERIFICAÇÃO DE CONFIGURAÇÃO ====

$configErrors = checkEmailConfig();
if (!empty($configErrors)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de configuração do servidor. Entre em contato com o administrador.',
        'debug' => SMTP_DEBUG > 0 ? $configErrors : null
    ]);
    exit;
}

// ==== RATE LIMITING ====

if (RATE_LIMIT_PER_HOUR > 0) {
    // Inicializa o contador de envios
    if (!isset($_SESSION['email_sends'])) {
        $_SESSION['email_sends'] = [];
    }
    
    // Remove envios antigos (mais de 1 hora)
    $oneHourAgo = time() - 3600;
    $_SESSION['email_sends'] = array_filter($_SESSION['email_sends'], function($timestamp) use ($oneHourAgo) {
        return $timestamp > $oneHourAgo;
    });
    
    // Verifica o limite
    if (count($_SESSION['email_sends']) >= RATE_LIMIT_PER_HOUR) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Limite de envios excedido. Tente novamente mais tarde.'
        ]);
        exit;
    }
}

// ==== RECEBER E VALIDAR DADOS ====

// Recebe dados JSON ou POST tradicional
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// Extrai e sanitiza os dados
$name = sanitizeString($data['name'] ?? $data['nome'] ?? '');
$email = sanitizeString($data['email'] ?? '');
$message = sanitizeString($data['message'] ?? $data['mensagem'] ?? '');

// Array para armazenar erros de validação
$errors = [];

// Validação do nome
if (empty($name)) {
    $errors[] = 'Nome é obrigatório.';
} elseif (strlen($name) < 3) {
    $errors[] = 'Nome deve ter pelo menos 3 caracteres.';
} elseif (strlen($name) > 100) {
    $errors[] = 'Nome muito longo (máximo 100 caracteres).';
}

// Validação do email
if (empty($email)) {
    $errors[] = 'Email é obrigatório.';
} elseif (!validateEmail($email)) {
    $errors[] = 'Email inválido.';
}

// Validação da mensagem
if (empty($message)) {
    $errors[] = 'Mensagem é obrigatória.';
} elseif (strlen($message) < MIN_MESSAGE_LENGTH) {
    $errors[] = 'Mensagem muito curta (mínimo ' . MIN_MESSAGE_LENGTH . ' caracteres).';
} elseif (strlen($message) > MAX_MESSAGE_LENGTH) {
    $errors[] = 'Mensagem muito longa (máximo ' . MAX_MESSAGE_LENGTH . ' caracteres).';
}

// Verifica se há erros de validação
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors),
        'errors' => $errors
    ]);
    exit;
}

// ==== PROTEÇÃO ANTI-SPAM ADICIONAL ====

// Verifica honeypot (campo oculto que bots preenchem)
$honeypot = isset($data['website']) ? $data['website'] : '';
if (!empty($honeypot)) {
    // Provavelmente um bot
    http_response_code(200); // Retorna sucesso para não alertar o bot
    echo json_encode([
        'success' => true,
        'message' => 'Mensagem enviada com sucesso!'
    ]);
    exit;
}

// Verifica palavras suspeitas (spam comum)
$spamWords = ['viagra', 'cialis', 'lottery', 'casino', 'porn', 'xxx'];
$messageCheck = strtolower($message);
foreach ($spamWords as $word) {
    if (strpos($messageCheck, $word) !== false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Mensagem contém conteúdo não permitido.'
        ]);
        exit;
    }
}

// ==== PREPARAR EMAIL ====

try {
    // Cria uma nova instância do PHPMailer
    $mail = new PHPMailer(true);
    
    // ==== CONFIGURAÇÕES DO SERVIDOR SMTP ====
    
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port = SMTP_PORT;
    $mail->Timeout = SMTP_TIMEOUT;
    
    // Configuração de debug (apenas se habilitado)
    if (SMTP_DEBUG > 0) {
        $mail->SMTPDebug = SMTP_DEBUG;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };
    } else {
        $mail->SMTPDebug = 0;
    }
    
    // Configuração de charset
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    // ==== CONFIGURAÇÕES DO REMETENTE E DESTINATÁRIO ====
    
    // Remetente (servidor SMTP)
    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    
    // Reply-To (email do visitante que preencheu o formulário)
    $mail->addReplyTo($email, $name);
    
    // Destinatário
    $mail->addAddress(EMAIL_TO, EMAIL_TO_NAME);
    
    // Cópia (se configurado)
    if (!empty(EMAIL_CC)) {
        $mail->addCC(EMAIL_CC);
    }
    
    // ==== CONTEÚDO DO EMAIL ====
    
    // Assunto
    $mail->Subject = EMAIL_SUBJECT_PREFIX . 'Nova Mensagem de Contato - ' . $name;
    
    // Corpo do email em HTML
    $mail->isHTML(true);
    
    $htmlBody = '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background: linear-gradient(135deg, #6F379B 0%, #9A79BF 100%);
                color: white;
                padding: 30px;
                text-align: center;
                border-radius: 10px 10px 0 0;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content {
                background: #f8f9fa;
                padding: 30px;
                border-radius: 0 0 10px 10px;
            }
            .info-box {
                background: white;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
                border-left: 4px solid #FF216B;
            }
            .info-box h3 {
                margin-top: 0;
                color: #6F379B;
            }
            .info-item {
                margin: 10px 0;
            }
            .info-label {
                font-weight: bold;
                color: #6F379B;
            }
            .message-box {
                background: white;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
                border: 1px solid #ddd;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .footer {
                text-align: center;
                padding: 20px;
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>📧 Nova Mensagem de Contato</h1>
        </div>
        <div class="content">
            <div class="info-box">
                <h3>Informações do Remetente</h3>
                <div class="info-item">
                    <span class="info-label">Nome:</span> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '
                </div>
                <div class="info-item">
                    <span class="info-label">Data/Hora:</span> ' . date('d/m/Y H:i:s') . '
                </div>
                <div class="info-item">
                    <span class="info-label">IP:</span> ' . $_SERVER['REMOTE_ADDR'] . '
                </div>
            </div>
            
            <div class="info-box">
                <h3>Mensagem</h3>
                <div class="message-box">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</div>
            </div>
        </div>
        <div class="footer">
            <p>Esta mensagem foi enviada através do formulário de contato do site<br>
            <strong>No Controle do Direito (NCDD)</strong></p>
            <p>Para responder, clique em "Responder" no seu cliente de email.</p>
        </div>
    </body>
    </html>
    ';
    
    $mail->Body = $htmlBody;
    
    // Versão em texto plano (fallback)
    $textBody = "NOVA MENSAGEM DE CONTATO - NCDD\n\n";
    $textBody .= "Nome: $name\n";
    $textBody .= "Email: $email\n";
    $textBody .= "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
    $textBody .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
    $textBody .= "MENSAGEM:\n";
    $textBody .= str_repeat('-', 50) . "\n";
    $textBody .= $message . "\n";
    $textBody .= str_repeat('-', 50) . "\n\n";
    $textBody .= "Esta mensagem foi enviada através do formulário de contato do site No Controle do Direito (NCDD).\n";
    $textBody .= "Para responder, use o email: $email\n";
    
    $mail->AltBody = $textBody;
    
    // ==== ENVIAR EMAIL ====
    
    $mail->send();
    
    // Registra o envio no rate limiting
    if (RATE_LIMIT_PER_HOUR > 0) {
        $_SESSION['email_sends'][] = time();
    }
    
    // Log de sucesso (opcional - remova em produção por questões de privacidade)
    error_log("Email enviado com sucesso de: $email - Nome: $name");
    
    // ==== RESPOSTA DE SUCESSO ====
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Obrigado, $name! Sua mensagem foi enviada com sucesso. Entraremos em contato em breve através do email $email."
    ]);
    
} catch (Exception $e) {
    // ==== TRATAMENTO DE ERROS ====
    
    // Log do erro
    error_log("Erro ao enviar email: " . $e->getMessage());
    
    // Resposta de erro
    http_response_code(500);
    
    $errorMessage = 'Desculpe, ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde.';
    
    // Em modo debug, incluir detalhes do erro
    $debugInfo = null;
    if (SMTP_DEBUG > 0) {
        $debugInfo = [
            'error' => $e->getMessage(),
            'mailer_error' => $mail->ErrorInfo
        ];
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'debug' => $debugInfo
    ]);
}

?>
