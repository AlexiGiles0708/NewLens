<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./Estilo/estilo.css">
    <title>NewsLens</title>
</head>
<body>
    <!-- Navbar con colores originales -->
    <nav>
        <div class="nav-wrapper">
            <div class="nav-wrapper">
                <img src="./Recursos/logo.png" alt="Logo NewsLens" class="logo">
                <span class="brand-text">NEWSLENS</span>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="main-container">
        <!-- Card del verificador con colores originales -->
        <div class="card verifier-card">
            <div class="card-content">
                <h4 class="center-align" style="color: #2C2C2C;">Verificador de Noticias</h4>
                <p class="center-align" style="color: #2C2C2C;">Ingresa el titular de la noticia que deseas verificar</p>
                
                <div class="search-container">
                    <input type="text" id="search" placeholder="Pega aquí el titular..." class="search-input">
                    <button id="verify-btn" class="btn btn-custom waves-effect waves-light">
                        <i class="material-icons left">search</i> Verificar
                    </button>
                </div>
            </div>
        </div>

        <!-- Sección informativa -->
        <div class="info-section">
            <h4 class="center-align" style="color: #2C2C2C;">¿Cómo funciona NewsLens?</h4>
            
            <div class="row">
                <div class="col s12 m4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <img src="./Recursos/ia_icono.jpg" alt="Tecnología IA">
                        </div>
                        <div class="card-content">
                            <span class="card-title" style="color: #2C2C2C;">Tecnología Avanzada</span>
                            <p style="color: #333333;">Nuestro sistema utiliza inteligencia artificial para detectar patrones de desinformación.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <img src="./Recursos/fake_news.jpg" alt="Detección de fake news">
                        </div>
                        <div class="card-content">
                            <span class="card-title" style="color: #2C2C2C;">Análisis Completo</span>
                            <p style="color: #333333;">Examinamos múltiples características lingüísticas de los titulares.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <img src="./Recursos/icono.png" alt="Resultados confiables">
                        </div>
                        <div class="card-content">
                            <span class="card-title" style="color: #2C2C2C;">Resultados Confiables</span>
                            <p style="color: #333333;">Te ayudamos a identificar posibles noticias falsas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de resultados -->
    <div id="result-modal" class="modal">
        <div class="modal-content">
            <h4 class="modal-title">Resultado del Análisis</h4>
            <div id="result-content">
                <!-- Contenido dinámico -->
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
        </div>
    </div>

    <!-- Footer con colores originales -->
    <footer class="page-footer">
        <div class="container">
            <p>&copy; 2025 NewsLens. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function(){
            $('.modal').modal();
        });

        document.getElementById('verify-btn').addEventListener('click', async function() {
            const texto = document.getElementById('search').value.trim();
            
            if (!texto) {
                M.toast({html: 'Por favor ingresa un titular', classes: 'red'});
                return;
            }

            try {
                M.toast({html: 'Analizando titular...', classes: 'blue', displayLength: 2000});

                const response = await fetch('http://144.126.132.105:5000/predict', {
                    method: 'POST',
                    mode: 'cors',  // Añadir esto explícitamente
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ texto: texto })
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Error en el servidor');
                }
                
                const data = await response.json();
                const modal = document.getElementById('result-modal');
                const resultContent = document.getElementById('result-content');
                
                if (data.resultado) {
                    resultContent.innerHTML = `
                        <p class="fake-result">
                            <i class="material-icons modal-icon">warning</i>
                            ¡FAKE NEWS DETECTADO!
                        </p>
                        <p>Este titular muestra características de noticias falsas.</p>
                        <p>Probabilidad: ${(data.probabilidad * 100).toFixed(2)}%</p>
                    `;
                } else {
                    resultContent.innerHTML = `
                        <p class="real-result">
                            <i class="material-icons modal-icon">check_circle</i>
                            NOTICIA VERIFICADA
                        </p>
                        <p>El análisis no detectó señales de fake news.</p>
                        <p>Probabilidad de ser fake: ${(data.probabilidad * 100).toFixed(2)}%</p>
                    `;
                }
                
                M.Modal.getInstance(modal).open();
                
            } catch (error) {
                console.error('Error:', error);
                M.toast({html: `Error: ${error.message}`, classes: 'red'});
            }
        });
    </script>
</body>
</html>