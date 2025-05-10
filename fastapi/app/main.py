from fastapi import FastAPI
from app.api.routes import similarity, patents

app = FastAPI()

@app.get("/")
def read_root():
    return {"message": "Hello, FastAPI!"}

app.include_router(similarity.router, prefix="/api")
app.include_router(patents.router, prefix="/api")