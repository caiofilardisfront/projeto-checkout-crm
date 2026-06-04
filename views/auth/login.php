<!-- Arquivo: /views/auth/login.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CRM-CHECKOUT</title>
    
    <!-- Bootstrap 5 via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Arquitetura Visual: Identidade Executiva Premium */
        :root {
            --deep-blue: #1A3A52;
            --tiffany-blue: #4FD1C5;
            --bg-light: #F4F7F6;
            --text-muted: #6C757D;
        }

        body {
            background-color: var(--bg-light);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }

        .login-card {
            background: #FFFFFF;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(26, 58, 82, 0.08);
            overflow: hidden;
        }

        .login-header {
            background-color: var(--deep-blue);
            padding: 35px 25px;
            text-align: center;
        }

        .login-header h1 {
            color: #FFFFFF;
            font-weight: 700;
            font-size: 1.6rem;
            letter-spacing: 1px;
            margin: 0;
        }

        .login-header p {
            color: var(--tiffany-blue);
            margin-top: 8px;
            margin-bottom: 0;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-body {
            padding: 35px 30px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--deep-blue);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--tiffany-blue);
            box-shadow: 0 0 0 3px rgba(79, 209, 197, 0.15);
            outline: none;
        }

        .btn-primary {
            background-color: var(--tiffany-blue);
            border-color: var(--tiffany-blue);
            color: var(--deep-blue);
            font-weight: 700;
            font-size: 1rem;
            padding: 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: #3bbcb0; /* Tom mais escuro para hover */
            border-color: #3bbcb0;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(79, 209, 197, 0.3);
        }

        .secure-badge {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .secure-badge svg {
            width: 16px;
            height: 16px;
            color: var(--tiffany-blue);
        }

        /* Container para injeção de erros via JS */
        #loginAlert {
            display: none;
            font-size: 0.9rem;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <h1>CRM-CHECKOUT</h1>
            <p>Gestão Comercial Executiva</p>
        </div>
        
        <div class="login-body">
            <!-- Container de Alerta Dinâmico -->
            <div id="loginAlert" class="alert alert-danger" role="alert"></div>

            <form id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail Corporativo</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="diretor@agencia.com.br" required autocomplete="email">
                </div>
                
                <div class="mb-4">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••" required autocomplete="current-password">
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <span id="btnText">Acessar Sistema</span>
                        <span id="btnLoader" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

            <div class="secure-badge">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                Conexão Segura e Criptografada
            </div>
        </div>
    </div>
</div>

<!-- O arquivo js/auth.js fará o controle via Fetch API assíncrona -->
<script src="/assets/js/auth.js"></script>
</body>
</html>