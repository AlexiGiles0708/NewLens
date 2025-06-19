from flask import Flask, request, jsonify, abort
import joblib
import spacy
from functools import lru_cache

app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 1024 * 1024  # Limitar a 1MB

# Carga los modelos con caché para evitar recargas
@lru_cache(maxsize=None)
def load_model():
    try:
        model = joblib.load('modelos/svm_unigram_binary.pkl')
        vectorizer = joblib.load('modelos/unigram_binary_vectorizer.pkl')
        nlp = spacy.load('es_core_news_sm')
        return model, vectorizer, nlp
    except Exception as e:
        app.logger.error(f"Error loading models: {str(e)}")
        raise

try:
    model, vectorizer, nlp = load_model()
except:
    # Si no podemos cargar los modelos, mejor fallar rápido
    raise SystemExit("No se pudieron cargar los modelos")

@app.route('/predict', methods=['POST'])
def predict():
    if not request.is_json:
        abort(400, description="Request must be JSON")
    
    data = request.get_json()
    texto = data.get('texto', '')
    
    if not texto or not isinstance(texto, str):
        abort(400, description="El campo 'texto' es requerido y debe ser una cadena")
    
    try:
        from fakenews.normalizacion.normalizacion3 import normalizar
        texto_norm = normalizar(texto, nlp)
        X_vec = vectorizer.transform([texto_norm])
        pred = model.predict(X_vec)[0]
        
        # Mejor devolver un booleano o número en lugar de string
        return jsonify({'resultado': bool(pred)}), 200
        
    except ImportError:
        abort(500, description="Error al importar el módulo de normalización")
    except Exception as e:
        app.logger.error(f"Prediction error: {str(e)}")
        abort(500, description="Error procesando la solicitud")

if __name__ == '__main__':
    app.run(port=5000, debug=False)  # debug=False en producción