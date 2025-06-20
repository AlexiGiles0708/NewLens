FROM python:3.9-slim

WORKDIR /app

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    build-essential \
    python3-dev \
    && rm -rf /var/lib/apt/lists/*

# Copia primero requirements.txt para cachear dependencias
COPY ./api/requirements.txt .

# Instala dependencias de Python
RUN pip install --no-cache-dir -r requirements.txt

# Copia todo el contenido de api
COPY ./api /app

# Descarga el modelo de lenguaje español
RUN python -m spacy download es_core_news_sm

EXPOSE 5000

CMD ["gunicorn", "--bind", "0.0.0.0:5000", "api:app"]