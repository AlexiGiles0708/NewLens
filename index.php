<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Estilo/estilo.css">
    <title>NewsLens</title>
</head>
<body>
    <section class="container">
        <header class="header_main">
            <h1>NewsLens</h1>
        </header>
        <form action="" class="formulario">
            <label for=""></label>
            <input type="text" name="search" id="search" placeholder="Verificar noticia...">
        </form>
    </section>
    
    <section class="container_informacion">    
        <div class="titulo_informacion">
            <h2>¿Qué es NewsLens?</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="./Recursos/ia_icono.jpg" alt="Icono de fake news" style="height: 100%;">
                </div>
                <h3>Idea principal del proyecto</h3>
                <p>NewsLens nace como respuesta a un problema actual: la desinformación digital. Usamos inteligencia artificial para ayudarte a identificar noticias falsas a partir de sus titulares, promoviendo el pensamiento crítico y el acceso a información confiable.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="./Recursos/fake_news.jpg" alt="Icono de inteligencia artificial" style="height: 100%;">
                </div>
                <h3>¿Por qué nace NewsLens?</h3>
                <p>NewsLens nace como una respuesta a la creciente propagación de noticias falsas en internet. Observamos cómo muchos usuarios comparten titulares sin verificar su veracidad, lo que contribuye a la desinformación. Por eso, desarrollamos una herramienta basada en inteligencia artificial que analiza los encabezados y alerta sobre posibles noticias falsas.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="./Recursos/icono.png" alt="Icono de análisis de titulares" style="height: 100%;">
                </div>
                <h3>Conclusión</h3>
                <p>NewsLens demuestra cómo la inteligencia artificial puede ser una aliada clave en la lucha contra la desinformación. A través del análisis automatizado de titulares, ofrecemos una herramienta accesible y útil para detectar posibles noticias falsas, fomentando el pensamiento crítico y el consumo responsable de información. Este proyecto no solo responde a una necesidad actual, sino que sienta las bases para seguir desarrollando soluciones tecnológicas que impulsen una sociedad mejor informada.</p>
            </div>
        </div>        
    </section>
    <footer class="footer">
        <div>
            <p>&copy; 2025 NewsLens. Giles Macias Alexis | Luciano Hernández Jonathan | Pacheco Morales Ramiro.</p>
        </div>
    </footer>
    <script>
        document.querySelector('.formulario').addEventListener('submit', async function(e) {
            e.preventDefault();
            const texto = document.getElementById('search').value;
            
            try {
                const respuesta = await fetch('http://localhost:5000/predict', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ texto: texto })
                });
                
                if (!respuesta.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                
                const data = await respuesta.json();
                
                // Mejora la visualización del resultado
                const resultadoDiv = document.createElement('div');
                resultadoDiv.className = 'resultado';
                resultadoDiv.innerHTML = `
                    <h3>Resultado del análisis:</h3>
                    <p><strong>Texto analizado:</strong> ${data.texto_original}</p>
                    <p><strong>Es noticia falsa:</strong> ${data.resultado ? 'Sí' : 'No'}</p>
                `;
                
                // Inserta el resultado después del formulario
                document.querySelector('.formulario').after(resultadoDiv);
                
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al procesar tu solicitud: ' + error.message);
            }
        });
    </script>
</body>
</html>