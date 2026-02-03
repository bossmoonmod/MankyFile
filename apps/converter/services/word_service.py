from docx import Document
from docxcompose.composer import Composer
from pathlib import Path

class WordService:
    def merge_word_files(self, file_paths, output_path):
        if not file_paths:
            return None

        # Open the first file as the master document
        master = Document(file_paths[0])
        composer = Composer(master)
        
        # Append the rest of the files
        for i in range(1, len(file_paths)):
            # Add a page break to start the new document on a new page
            master.add_page_break()
            
            sub_doc = Document(file_paths[i])
            composer.append(sub_doc)
                
        composer.save(output_path)
        return output_path
