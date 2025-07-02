# check_env.py
from dotenv import load_dotenv
import os

# Load the .env file
load_dotenv(dotenv_path="/root/website-patent/fastapi/.env")

# Print important environment variables
print("APP_NAME:", os.getenv("APP_NAME"))
print("DB_HOST:", os.getenv("DB_HOST"))
print("DB_USER:", os.getenv("DB_USER"))
print("ELASTICSEARCH_HOST:", os.getenv("ELASTICSEARCH_HOST"))
print("MILVUS_HOST:", os.getenv("MILVUS_HOST"))
print("API KEY", os.getenv("API_KEY"))
