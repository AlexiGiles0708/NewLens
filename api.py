from flask import Flask, request, jsonify, abort
import joblib
import spacy
import pandas as pd
import numpy as np
import re
from functools import lru_cache
from flask_cors import CORS
from spacy.lang.es.stop_words import STOP_WORDS

app = Flask(__name__)
CORS(app)
app.config['MAX_CONTENT_LENGTH'] = 1024 * 1024  # Limitar a 1MB

# --------------------------
# Funciones de preprocesamiento
# --------------------------

def tokenize_text(text):
    """Función de tokenización idéntica a la usada en entrenamiento"""
    return text.split()

def normalizar(texto, nlp):
    """Función de normalización idéntica a la usada en entrenamiento"""
    instituciones = {
        r'\b(rae|real academia española)\b': 'inst_rae',
        r'\b(sep|secretaría de educación pública)\b': 'inst_sep',
        r'\bunam\b': 'inst_unam',
        r'\b(ine|instituto nacional electoral)\b': 'inst_ine'
    }
    
    texto = texto.lower()
    for pat, repl in instituciones.items():
        texto = re.sub(pat, repl, texto)
    
    texto = re.sub(r'\d+', 'NUM', texto)
    
    sensacionalistas = {
        r'\b(descubr[íi]|revel[óo]|impactante|esc[áa]ndalo)\b': 'sensacional',
        r'!{2,}': 'MULTI_EXCL',
        r'\?{2,}': 'MULTI_QUEST'
    }
    for pat, repl in sensacionalistas.items():
        texto = re.sub(pat, repl, texto)
    
    doc = nlp(texto)
    tokens = []
    for token in doc:
        if not token.is_space:
            if token.is_punct:
                if token.text in ['!', '¡']:
                    tokens.append('EXCL')
                elif token.text in ['?', '¿']:
                    tokens.append('QUEST')
            else:
                lemma = token.lemma_
                if token.pos_ in ['VERB', 'AUX']:
                    tokens.append(f"verb_{lemma}")
                elif token.ent_type_:
                    tokens.append(f"ent_{token.ent_type_}_{lemma}")
                elif len(lemma) > 2 and lemma not in STOP_WORDS:
                    tokens.append(lemma)
    
    return ' '.join(tokens)

def agregar_caracteristicas(texto):
    """Calcula las mismas características que durante el entrenamiento"""
    features = {}
    
    features['longitud'] = len(texto)
    features['num_palabras'] = len(texto.split())
    features['palabras_unicas'] = len(set(texto.split()))
    features['palabras_unicas_ratio'] = features['palabras_unicas'] / features['num_palabras'] if features['num_palabras'] > 0 else 0
    features['mayusculas'] = sum(1 for c in texto if c.isupper())
    features['exclamaciones'] = texto.count('!') + texto.count('¡')
    features['interrogaciones'] = texto.count('?') + texto.count('¿')
    features['puntuacion_total'] = features['exclamaciones'] + features['interrogaciones']
    
    sensacionalistas = [
        'urgente', 'exclusivo', 'impactante', 'revelación', 
        'escándalo', 'impacto', 'descubre', 'revela', 'sorprende',
        'asombroso', 'increíble', 'aterrador', 'alerta', 'peligro',
        'shock', 'bomba', 'explosivo', 'oculto', 'censurado', 'conspiración'
    ]
    features['sensacionalistas'] = sum(texto.count(palabra) for palabra in sensacionalistas)
    
    features['contiene_url'] = 1 if 'http' in texto else 0
    features['num_entidades'] = texto.count('ent_')
    features['num_citas'] = texto.count('"')
    features['nombres_propios'] = len(re.findall(r'\b[A-Z][a-z]+\b', texto))
    
    # Polaridad
    palabras_positivas = ['bueno', 'excelente', 'maravilloso', 'genial', 'positivo']
    palabras_negativas = ['malo', 'terrible', 'horrible', 'pésimo', 'negativo']
    features['polaridad_pos'] = sum(texto.count(palabra) for palabra in palabras_positivas)
    features['polaridad_neg'] = sum(texto.count(palabra) for palabra in palabras_negativas)
    
    return features

# --------------------------
# Carga del modelo
# --------------------------

@lru_cache(maxsize=None)
def load_model():
    try:
        # Cargar el pipeline completo guardado
        pipeline = joblib.load('fakenews/modelos/optimized_model.pkl')
        nlp = spacy.load('es_core_news_sm', disable=['parser', 'ner'])
        return pipeline, nlp
    except Exception as e:
        app.logger.error(f"Error loading models: {str(e)}")
        raise

try:
    model, nlp = load_model()
    print("✅ Modelo y NLP cargados correctamente")
except Exception as e:
    raise SystemExit(f"❌ No se pudieron cargar los modelos: {str(e)}")

# --------------------------
# Endpoints
# --------------------------

@app.route('/')
def home():
    """Endpoint raíz para verificar que la API está funcionando"""
    return jsonify({
        "message": "API de detección de fake news",
        "status": "operacional",
        "endpoints": {
            "predict": {
                "url": "/predict",
                "method": "POST",
                "params": {"texto": "string"},
                "description": "Analiza si una noticia es falsa"
            }
        }
    }), 200

@app.route('/favicon.ico')
def favicon():
    """Evita errores de favicon"""
    return '', 404

@app.route('/predict', methods=['POST'])
def predict():
    """Endpoint principal para predicciones"""
    if not request.is_json:
        abort(400, description="Request must be JSON")
    
    data = request.get_json()
    texto = data.get('texto', '')
    
    if not texto or not isinstance(texto, str):
        abort(400, description="El campo 'texto' es requerido y debe ser una cadena")
    
    try:
        # 1. Normalizar el texto
        texto_norm = normalizar(texto, nlp)
        
        # 2. Crear DataFrame con la estructura de entrenamiento
        features = agregar_caracteristicas(texto_norm)
        
        input_data = pd.DataFrame({
            'Text': [texto_norm],
            'longitud': [features['longitud']],
            'num_palabras': [features['num_palabras']],
            'palabras_unicas': [features['palabras_unicas']],
            'palabras_unicas_ratio': [features['palabras_unicas_ratio']],
            'mayusculas': [features['mayusculas']],
            'exclamaciones': [features['exclamaciones']],
            'interrogaciones': [features['interrogaciones']],
            'puntuacion_total': [features['puntuacion_total']],
            'sensacionalistas': [features['sensacionalistas']],
            'contiene_url': [features['contiene_url']],
            'num_entidades': [features['num_entidades']],
            'polaridad_pos': [features['polaridad_pos']],
            'polaridad_neg': [features['polaridad_neg']],
            'num_citas': [features['num_citas']],
            'nombres_propios': [features['nombres_propios']]
        })
        
        # 3. Hacer la predicción
        pred = model.predict(input_data)[0]
        proba = model.predict_proba(input_data)[0][1]  # Probabilidad de ser fake
        
        return jsonify({
            'resultado': bool(pred),
            'probabilidad': float(proba),
            'texto_original': texto,
            'texto_normalizado': texto_norm,
            'features': features  # Opcional: para debugging
        }), 200
        
    except Exception as e:
        app.logger.error(f"Prediction error: {str(e)}")
        abort(500, description=f"Error procesando la solicitud: {str(e)}")

# --------------------------
# Inicialización
# --------------------------

if __name__ == '__main__':
    print("\n🔍 Puedes probar los siguientes endpoints:")
    print(" - GET  http://localhost:5000/")
    print(" - POST http://localhost:5000/predict")
    app.run(host='0.0.0.0', port=5000, debug=False)