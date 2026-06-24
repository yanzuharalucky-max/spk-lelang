import pandas as pd
import numpy as np
from typing import List


class SAWMethod:
    def __init__(self, data: pd.DataFrame, weights: List[float], criteria_type: List[str]):
        """
        Inisialisasi metode SAW

        Parameters:
        - data: DataFrame dengan kolom pertama sebagai alternatif, sisanya sebagai kriteria
        - weights: List bobot untuk setiap kriteria
        - criteria_type: List tipe kriteria ('benefit' atau 'cost')
        """
        self.data = data.copy()
        self.weights = np.array(weights)
        self.criteria_type = criteria_type
        self.normalized_data = None
        self.scores = None

    def validate_input(self):
        """Validasi jumlah bobot dan tipe kriteria"""
        criteria_count = len(self.data.columns) - 1

        if len(self.weights) != criteria_count:
            raise ValueError("Jumlah bobot harus sama dengan jumlah kriteria.")

        if len(self.criteria_type) != criteria_count:
            raise ValueError("Jumlah tipe kriteria harus sama dengan jumlah kriteria.")

        for c_type in self.criteria_type:
            if c_type not in ["benefit", "cost"]:
                raise ValueError("Tipe kriteria hanya boleh 'benefit' atau 'cost'.")

    def normalize_matrix(self) -> pd.DataFrame:
        """Normalisasi matriks keputusan"""
        self.validate_input()

        normalized_data = self.data.copy()

        for i, col in enumerate(self.data.columns[1:]):  # Skip kolom pertama alternatif
            if self.criteria_type[i] == "benefit":
                max_val = self.data[col].max()

                if max_val == 0:
                    normalized_data[col] = 0
                else:
                    normalized_data[col] = self.data[col] / max_val

            elif self.criteria_type[i] == "cost":
                min_val = self.data[col].min()

                normalized_data[col] = np.where(
                    self.data[col] != 0,
                    min_val / self.data[col],
                    0
                )

        self.normalized_data = normalized_data
        return normalized_data

    def calculate_scores(self) -> pd.DataFrame:
        """Hitung skor akhir dan ranking"""
        if self.normalized_data is None:
            self.normalize_matrix()

        criteria_columns = self.normalized_data.columns[1:]

        weighted_scores = self.normalized_data[criteria_columns] * self.weights

        self.normalized_data["Score"] = weighted_scores.sum(axis=1)
        self.normalized_data["Rank"] = self.normalized_data["Score"].rank(
            ascending=False,
            method="min"
        ).astype(int)

        self.scores = self.normalized_data
        return self.normalized_data

    def get_ranking(self) -> pd.DataFrame:
        """Dapatkan hasil ranking"""
        if self.scores is None:
            self.calculate_scores()

        alt_column = self.scores.columns[0]

        result = self.scores[[alt_column, "Score", "Rank"]].sort_values("Rank")
        result = result.rename(columns={alt_column: "Alternatif"})

        return result

    def display_dataframe(self, df: pd.DataFrame):
        """Menampilkan dataframe agar rapi di terminal atau Google Colab"""
        try:
            from IPython.display import display
            display(df)
        except Exception:
            print(df.to_string(index=False))

    def display_results(self):
        """Tampilkan hasil lengkap"""
        print("=" * 70)
        print("SISTEM PENDUKUNG KEPUTUSAN - METODE SAW")
        print("=" * 70)

        print("\nDATA AWAL:")
        self.display_dataframe(self.data)

        print(f"\nBOBOT KRITERIA: {list(self.weights)}")
        print(f"TIPE KRITERIA : {self.criteria_type}")

        print("\nDATA NORMALISASI:")
        normalized = self.normalize_matrix()
        self.display_dataframe(normalized.round(4))

        print("\nHASIL RANKING:")
        ranking = self.get_ranking()
        self.display_dataframe(ranking.round(4))

        best_alternative = ranking.iloc[0]["Alternatif"]
        best_score = ranking.iloc[0]["Score"]

        print(f"\nALTERNATIF TERBAIK: {best_alternative}")
        print(f"SKOR: {best_score:.4f}")
        print("=" * 70)


def contoh_pemilihan_laptop():
    """Contoh 1: Pemilihan Laptop Terbaik"""
    print("\nCONTOH 1: PEMILIHAN LAPTOP")
    print("=" * 50)

    data = pd.DataFrame({
        "Alternatif": ["Laptop A", "Laptop B", "Laptop C", "Laptop D", "Laptop E"],
        "Harga (juta)": [8, 12, 15, 10, 9],
        "RAM (GB)": [8, 16, 32, 16, 8],
        "Storage (GB)": [256, 512, 1000, 512, 256],
        "Baterai (jam)": [6, 8, 5, 7, 9]
    })

    weights = [0.35, 0.25, 0.25, 0.15]
    criteria_type = ["cost", "benefit", "benefit", "benefit"]

    saw = SAWMethod(data, weights, criteria_type)
    saw.display_results()

    return saw


def contoh_seleksi_karyawan():
    """Contoh 2: Seleksi Karyawan Terbaik"""
    print("\nCONTOH 2: SELEKSI KARYAWAN")
    print("=" * 50)

    data = pd.DataFrame({
        "Alternatif": ["Andi", "Budi", "Cici", "Dedi", "Eka"],
        "Pengalaman (tahun)": [3, 5, 2, 4, 6],
        "Nilai Test": [85, 92, 78, 88, 95],
        "Gaji Ekspektasi (juta)": [8, 12, 6, 10, 15],
        "Usia": [25, 30, 28, 35, 32]
    })

    weights = [0.30, 0.35, 0.25, 0.10]
    criteria_type = ["benefit", "benefit", "cost", "benefit"]

    saw = SAWMethod(data, weights, criteria_type)
    saw.display_results()

    return saw


def contoh_pemilihan_supplier():
    """Contoh 3: Pemilihan Supplier Terbaik"""
    print("\nCONTOH 3: PEMILIHAN SUPPLIER")
    print("=" * 50)

    data = pd.DataFrame({
        "Alternatif": ["Supplier A", "Supplier B", "Supplier C", "Supplier D"],
        "Harga": [45000, 48000, 42000, 46000],
        "Kualitas": [90, 85, 92, 88],
        "Pengiriman (hari)": [3, 5, 2, 4],
        "Pelayanan": [85, 80, 90, 75]
    })

    weights = [0.40, 0.30, 0.20, 0.10]
    criteria_type = ["cost", "benefit", "cost", "benefit"]

    saw = SAWMethod(data, weights, criteria_type)
    saw.display_results()

    return saw


def contoh_pemilihan_pemenang_lelang():
    """Contoh 4: Pemilihan Pemenang Lelang Menggunakan SAW"""
    print("\nCONTOH 4: PEMILIHAN PEMENANG LELANG")
    print("=" * 50)

    data = pd.DataFrame({
        "Alternatif": ["Vendor A", "Vendor B", "Vendor C", "Vendor D"],
        "Harga Penawaran": [95000000, 90000000, 100000000, 87000000],
        "Kualitas Barang": [85, 80, 90, 78],
        "Waktu Pengerjaan": [14, 10, 18, 12],
        "Pengalaman": [4, 5, 6, 3],
        "Rating Vendor": [4.3, 4.5, 4.7, 4.1]
    })

    weights = [0.30, 0.25, 0.20, 0.15, 0.10]

    criteria_type = [
        "cost",      # Harga semakin kecil semakin baik
        "benefit",   # Kualitas semakin besar semakin baik
        "cost",      # Waktu semakin cepat semakin baik
        "benefit",   # Pengalaman semakin besar semakin baik
        "benefit"    # Rating semakin besar semakin baik
    ]

    saw = SAWMethod(data, weights, criteria_type)
    saw.display_results()

    return saw


def input_data_manual():
    """Input data manual dari user"""
    print("\nINPUT DATA MANUAL")
    print("=" * 40)

    try:
        n_alternatives = int(input("Jumlah alternatif: "))
        n_criteria = int(input("Jumlah kriteria: "))

        alternatives = []
        print("\nMasukkan nama alternatif:")
        for i in range(n_alternatives):
            alt = input(f"Alternatif {i + 1}: ")
            alternatives.append(alt)

        criteria = []
        criteria_type = []

        print("\nMasukkan nama kriteria dan tipe benefit/cost:")
        for i in range(n_criteria):
            crit = input(f"Nama kriteria {i + 1}: ")

            c_type = input(f"Tipe {crit} (benefit/cost): ").lower()

            while c_type not in ["benefit", "cost"]:
                print("Tipe harus 'benefit' atau 'cost'!")
                c_type = input(f"Tipe {crit} (benefit/cost): ").lower()

            criteria.append(crit)
            criteria_type.append(c_type)

        print("\nMasukkan nilai untuk setiap alternatif:")
        data_dict = {"Alternatif": alternatives}

        for i, crit in enumerate(criteria):
            values = []
            print(f"\n{crit} ({criteria_type[i]}):")

            for alt in alternatives:
                val = float(input(f"  {alt}: "))
                values.append(val)

            data_dict[crit] = values

        df = pd.DataFrame(data_dict)

        print(f"\nMasukkan bobot untuk {n_criteria} kriteria:")
        weights = []

        for crit in criteria:
            w = float(input(f"Bobot {crit}: "))
            weights.append(w)

        total_weight = sum(weights)

        if abs(total_weight - 1.0) > 0.001:
            print(f"\nPeringatan: Total bobot ({total_weight:.3f}) tidak sama dengan 1.0.")
            normalize = input("Normalisasi bobot otomatis? (y/n): ").lower()

            if normalize == "y":
                weights = [w / total_weight for w in weights]
                print(f"Bobot setelah normalisasi: {[round(w, 4) for w in weights]}")

        return df, weights, criteria_type

    except ValueError:
        print("Error: Input tidak valid. Pastikan memasukkan angka yang benar.")
        return None, None, None

    except Exception as e:
        print(f"Error: {e}")
        return None, None, None


def main():
    """Program utama"""
    print("SPK - METODE SAW (Simple Additive Weighting)")
    print("=" * 60)

    while True:
        print("\nPilih opsi:")
        print("1. Contoh Pemilihan Laptop")
        print("2. Contoh Seleksi Karyawan")
        print("3. Contoh Pemilihan Supplier")
        print("4. Contoh Pemilihan Pemenang Lelang")
        print("5. Input Data Manual")
        print("6. Keluar")

        pilihan = input("\nPilihan (1-6): ").strip()

        if pilihan == "1":
            contoh_pemilihan_laptop()

        elif pilihan == "2":
            contoh_seleksi_karyawan()

        elif pilihan == "3":
            contoh_pemilihan_supplier()

        elif pilihan == "4":
            contoh_pemilihan_pemenang_lelang()

        elif pilihan == "5":
            data, weights, criteria_type = input_data_manual()

            if data is not None and weights is not None and criteria_type is not None:
                saw = SAWMethod(data, weights, criteria_type)
                saw.display_results()

        elif pilihan == "6":
            print("\nTerima kasih telah menggunakan program SAW.")
            break

        else:
            print("Pilihan tidak valid. Silakan pilih 1-6.")

        if pilihan != "6":
            lanjut = input("\nLanjut ke menu utama? (y/n): ").lower()

            if lanjut != "y":
                print("\nTerima kasih telah menggunakan program SAW.")
                break


def demo_otomatis():
    """Jalankan semua contoh secara otomatis"""
    print("DEMO OTOMATIS - METODE SAW")
    print("=" * 60)

    print("\nMenjalankan contoh Pemilihan Laptop...")
    contoh_pemilihan_laptop()

    print("\nMenjalankan contoh Seleksi Karyawan...")
    contoh_seleksi_karyawan()

    print("\nMenjalankan contoh Pemilihan Supplier...")
    contoh_pemilihan_supplier()

    print("\nMenjalankan contoh Pemilihan Pemenang Lelang...")
    contoh_pemilihan_pemenang_lelang()

    print("\n" + "=" * 60)
    print("DEMO SELESAI")
    print("=" * 60)


if __name__ == "__main__":
    print("Pilih mode:")
    print("1. Mode Interaktif")
    print("2. Mode Demo Otomatis")

    mode = input("Pilih mode (1/2): ").strip()

    if mode == "1":
        main()

    elif mode == "2":
        demo_otomatis()

    else:
        print("Mode tidak valid, menjalankan Mode Interaktif...")
        main()