<?php
/**
 * =================================================================
 * CONFIGURAÇÕES DE EMAIL - NO CONTROLE DO DIREITO
 * =================================================================
 * 
 * IMPORTANTE: Este arquivo contém informações sensíveis!
 * NÃO compartilhe este arquivo publicamente.
 * 
 * INSTRUÇÕES:
 * Preencha os valores abaixo com as credenciais fornecidas pela Hostinger.
 * 
 * =================================================================
 */

// ==== CONFIGURAÇÕES DO SERVIDOR SMTP ====

/**
 * Servidor SMTP da Hostinger
 * Valor padrão: smtp.hostinger.com
 * NÃO ALTERE este valor a menos que seu provedor seja diferente
 */
define('SMTP_HOST', 'smtp.hostinger.com');

/**
 * Porta SMTP
 * 465 = SSL (Recomendado para Hostinger)
 * 587 = TLS (Alternativa)
 * NÃO ALTERE a menos que solicitado pelo suporte
 */
define('SMTP_PORT', 465);

/**
 * Tipo de criptografia
 * 'ssl' = Conexão SSL (usar com porta 465)
 * 'tls' = Conexão TLS (usar com porta 587)
 */
define('SMTP_ENCRYPTION', 'ssl');

// ==== CREDENCIAIS DE EMAIL ====

/**
 * Endereço de email completo
 * ALTERE: Coloque seu email completo aqui
 * Exemplo: contato@nocontroledodireito.com.br
 */
define('SMTP_USERNAME', 'seuemail@seudominio.com.br');

/**
 * Senha do email
 * ALTERE: Coloque a senha do seu email aqui
 * Esta é a mesma senha que você usa para acessar seu email
 * MANTENHA ESTA SENHA SEGURA!
 */
define('SMTP_PASSWORD', 'SuaSenhaAqui123');

// ==== CONFIGURAÇÕES DE ENVIO ====

/**
 * Email que aparecerá como remetente
 * Geralmente é o mesmo que SMTP_USERNAME
 */
define('EMAIL_FROM', 'seuemail@seudominio.com.br');

/**
 * Nome que aparecerá como remetente
 * Este nome será exibido nos emails recebidos
 */
define('EMAIL_FROM_NAME', 'No Controle do Direito - NCDD');

/**
 * Email para onde as mensagens do formulário serão enviadas
 * ALTERE: Coloque o email onde deseja receber as mensagens
 * Pode ser o mesmo ou diferente do email de envio
 */
define('EMAIL_TO', 'administrativo@nocontroledodireito.com.br');

/**
 * Nome do destinatário
 */
define('EMAIL_TO_NAME', 'Administrativo NCDD');

/**
 * Email para cópias (opcional)
 * Deixe vazio ('') se não quiser enviar cópia
 * Exemplo: 'backup@nocontroledodireito.com.br'
 */
define('EMAIL_CC', '');

/**
 * Assunto padrão dos emails
 */
define('EMAIL_SUBJECT_PREFIX', '[NCDD] ');

// ==== CONFIGURAÇÕES DE SEGURANÇA ====

/**
 * Domínios permitidos para enviar emails (separados por vírgula)
 * Deixe vazio para permitir qualquer domínio
 * Exemplo: 'nocontroledodireito.com.br,seuoutrodominio.com.br'
 */
define('ALLOWED_DOMAINS', '');

/**
 * Ativar modo de depuração
 * 0 = Desligado (use em produção)
 * 1 = Mensagens do cliente
 * 2 = Mensagens do cliente e servidor
 * 3 = Modo debug completo
 * 4 = Debug de baixo nível
 */
define('SMTP_DEBUG', 0);

/**
 * Timeout de conexão (em segundos)
 * Recomendado: 10
 */
define('SMTP_TIMEOUT', 10);

// ==== CONFIGURAÇÕES DE VALIDAÇÃO ====

/**
 * Tamanho máximo da mensagem (em caracteres)
 */
define('MAX_MESSAGE_LENGTH', 5000);

/**
 * Tamanho mínimo da mensagem (em caracteres)
 */
define('MIN_MESSAGE_LENGTH', 10);

/**
 * Limite de envios por hora (anti-spam)
 * 0 = sem limite
 */
define('RATE_LIMIT_PER_HOUR', 10);

// ==== NÃO ALTERE ABAIXO DESTA LINHA ====

/**
 * Verificação básica de configuração
 * Não modifique este código
 */
function checkEmailConfig() {
    $errors = [];
    
    if (SMTP_USERNAME === 'seuemail@seudominio.com.br') {
        $errors[] = 'SMTP_USERNAME precisa ser configurado';
    }
    
    if (SMTP_PASSWORD === 'SuaSenhaAqui123') {
        $errors[] = 'SMTP_PASSWORD precisa ser configurado';
    }
    
    if (EMAIL_FROM === 'seuemail@seudominio.com.br') {
        $errors[] = 'EMAIL_FROM precisa ser configurado';
    }
    
    return $errors;
}

/**
 * Validar email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitizar string
 */
function sanitizeString($string) {
    return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
}

?>
