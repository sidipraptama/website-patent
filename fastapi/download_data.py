import kagglehub
import os

def download_dataset(dataset_name, save_path):
    """
    Fungsi untuk mendownload dataset dari KaggleHub dan menyimpannya di path yang ditentukan.
    """
    # Unduh dataset ke folder yang ditentukan
    print(f"Downloading dataset {dataset_name}...")
    path = kagglehub.dataset_download(dataset_name)
    
    # Pindahkan dataset ke folder yang diinginkan
    if not os.path.exists(save_path):
        os.makedirs(save_path)
    
    # Menyimpan dataset di path yang diinginkan
    os.rename(path, os.path.join(save_path, os.path.basename(path)))
    
    print(f"Dataset downloaded to: {save_path}")
    return save_path


if __name__ == "__main__":
    # Tentukan nama dataset dan path folder untuk menyimpan dataset
    dataset_name = "sidipraptama/sberta-2024"
    save_path = "/var/www/app/website-patent/fastapi/data"

    # Panggil fungsi download
    download_dataset(dataset_name, save_path)
