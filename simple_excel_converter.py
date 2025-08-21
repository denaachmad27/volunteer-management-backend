#!/usr/bin/env python3
"""
Simple script to convert CSV to Excel
"""

import pandas as pd
import os
from datetime import datetime
import glob

def convert_csv_to_excel():
    """Convert latest CSV files to Excel"""
    
    # Find latest CSV files
    csv_files = glob.glob("Data_Relawan_Lengkap_*.csv")
    family_csv_files = glob.glob("Detail_Keluarga_Relawan_*.csv")
    
    if not csv_files:
        print("No CSV files found!")
        return
    
    # Get latest CSV files
    main_csv = max(csv_files, key=os.path.getctime)
    family_csv = max(family_csv_files, key=os.path.getctime) if family_csv_files else None
    
    print(f"Reading file: {main_csv}")
    
    # Read CSV with UTF-8 encoding
    df_main = pd.read_csv(main_csv, encoding='utf-8')
    
    # Generate Excel filename
    timestamp = datetime.now().strftime('%Y-%m-%d_%H-%M-%S')
    excel_filename = f"Data_Relawan_Lengkap_{timestamp}.xlsx"
    
    # Create Excel writer
    with pd.ExcelWriter(excel_filename, engine='openpyxl') as writer:
        # Sheet 1: Main volunteer data
        df_main.to_excel(writer, sheet_name='Data Relawan', index=False)
        
        # Sheet 2: Family details (if exists)
        if family_csv and os.path.exists(family_csv):
            print(f"Reading family file: {family_csv}")
            df_family = pd.read_csv(family_csv, encoding='utf-8')
            df_family.to_excel(writer, sheet_name='Detail Keluarga', index=False)
        
        # Sheet 3: Summary statistics
        create_summary_sheet(df_main, writer)
    
    print(f"Excel file created: {excel_filename}")
    print(f"Total volunteers: {len(df_main)}")
    
    # Calculate statistics
    active_volunteers = len(df_main[df_main['Status'] == 'Aktif'])
    with_profile = len(df_main[df_main['NIK'].notna()])
    with_phone = len(df_main[df_main['Telepon'].notna()])
    
    print(f"Active volunteers: {active_volunteers}")
    print(f"With complete profile: {with_profile}")
    print(f"With phone number: {with_phone}")

def create_summary_sheet(df, writer):
    """Create summary statistics sheet"""
    
    summary_data = {
        'Kategori': [
            'Total Relawan',
            'Relawan Aktif', 
            'Relawan Tidak Aktif',
            'Memiliki Profil Lengkap (NIK)',
            'Memiliki Nomor Telepon',
            'Memiliki Data Ekonomi',
            'Memiliki Data Sosial',
            'Memiliki Anggota Keluarga'
        ],
        'Jumlah': [
            len(df),
            len(df[df['Status'] == 'Aktif']),
            len(df[df['Status'] == 'Tidak Aktif']),
            len(df[df['NIK'].notna()]),
            len(df[df['Telepon'].notna()]),
            len(df[df['Penghasilan Bulanan'].notna()]),
            len(df[df['Organisasi'].notna()]),
            len(df[df['Jumlah Anggota Keluarga'] > 0])
        ],
        'Persentase': []
    }
    
    total = len(df)
    for jumlah in summary_data['Jumlah']:
        persen = (jumlah / total * 100) if total > 0 else 0
        summary_data['Persentase'].append(f"{persen:.1f}%")
    
    df_summary = pd.DataFrame(summary_data)
    df_summary.to_excel(writer, sheet_name='Ringkasan Statistik', index=False)

if __name__ == "__main__":
    try:
        convert_csv_to_excel()
    except ImportError:
        print("Error: pandas and openpyxl required!")
        print("Install with: pip install pandas openpyxl")
    except Exception as e:
        print(f"Error: {e}")