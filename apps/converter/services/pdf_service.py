import PyPDF2
from pathlib import Path

class PDFService:
    def merge_pdfs(self, file_paths, output_path):
        merger = PyPDF2.PdfMerger()
        for path in file_paths:
            merger.append(path)
        merger.write(output_path)
        merger.close()
        return output_path

    def split_pdf(self, file_path, output_dir):
        # Implementation for splitting
        pass
