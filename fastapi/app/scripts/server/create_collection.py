import os
import numpy as np
from sklearn.preprocessing import normalize
from app.db.milvus import (
    connect_milvus,
    create_collection_sberta,
    create_index_sberta,
    check_index_sberta,
)

connect_milvus()
create_collection_sberta()
create_index_sberta()
check_index_sberta()