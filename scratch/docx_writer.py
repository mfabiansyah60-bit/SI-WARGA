# -*- coding: utf-8 -*-
"""
Generator Dokumen PRD / SRS SiWarga
Standar: IEEE 830 / ISO/IEC/IEEE 29148
Output: PRD_SiWarga.docx
"""

import os
import zipfile
import html

class DocxWriter:
    def __init__(self):
        self.body_elements = []
        
    def escape(self, text):
        if text is None:
            return ""
        # Escape XML entities
        return (str(text)
                .replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace('"', "&quot;")
                .replace("'", "&apos;"))

    def add_title(self, title, subtitle=None):
        xml = f"""
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:before="360" w:after="160"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                    <w:b/>
                    <w:sz w:val="40"/>
                    <w:color w:val="1E3A8A"/>
                </w:rPr>
                <w:t xml:space="preserve">{self.escape(title)}</w:t>
            </w:r>
        </w:p>
        """
        self.body_elements.append(xml)
        if subtitle:
            sub_xml = f"""
            <w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                    <w:spacing w:before="0" w:after="320"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                        <w:i/>
                        <w:sz w:val="24"/>
                        <w:color w:val="475569"/>
                    </w:rPr>
                    <w:t xml:space="preserve">{self.escape(subtitle)}</w:t>
                </w:r>
            </w:p>
            """
            self.body_elements.append(sub_xml)

    def add_heading(self, text, level=1):
        config = {
            1: {"sz": 32, "color": "1E3A8A", "before": 360, "after": 140, "bold": True},
            2: {"sz": 26, "color": "1D4ED8", "before": 240, "after": 100, "bold": True},
            3: {"sz": 24, "color": "0F172A", "before": 180, "after": 80, "bold": True},
            4: {"sz": 22, "color": "334155", "before": 140, "after": 60, "bold": True, "italic": True}
        }
        cfg = config.get(level, config[3])
        b_tag = "<w:b/>" if cfg.get("bold") else ""
        i_tag = "<w:i/>" if cfg.get("italic") else ""
        
        xml = f"""
        <w:p>
            <w:pPr>
                <w:spacing w:before="{cfg['before']}" w:after="{cfg['after']}"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                    {b_tag}
                    {i_tag}
                    <w:sz w:val="{cfg['sz']}"/>
                    <w:color w:val="{cfg['color']}"/>
                </w:rPr>
                <w:t xml:space="preserve">{self.escape(text)}</w:t>
            </w:r>
        </w:p>
        """
        self.body_elements.append(xml)

    def add_paragraph(self, text, bold_prefix="", italic_suffix="", justify=True, space_after=140):
        jc_val = "both" if justify else "left"
        r_xml = ""
        if bold_prefix:
            r_xml += f"""
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                    <w:b/>
                    <w:sz w:val="22"/>
                    <w:color w:val="0F172A"/>
                </w:rPr>
                <w:t xml:space="preserve">{self.escape(bold_prefix)} </w:t>
            </w:r>
            """
        r_xml += f"""
        <w:r>
            <w:rPr>
                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                <w:sz w:val="22"/>
                <w:color w:val="1E293B"/>
            </w:rPr>
            <w:t xml:space="preserve">{self.escape(text)}</w:t>
        </w:r>
        """
        if italic_suffix:
            r_xml += f"""
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                    <w:i/>
                    <w:sz w:val="22"/>
                    <w:color w:val="475569"/>
                </w:rPr>
                <w:t xml:space="preserve"> {self.escape(italic_suffix)}</w:t>
            </w:r>
            """

        xml = f"""
        <w:p>
            <w:pPr>
                <w:jc w:val="{jc_val}"/>
                <w:spacing w:line="276" w:lineRule="auto" w:after="{space_after}"/>
            </w:pPr>
            {r_xml}
        </w:p>
        """
        self.body_elements.append(xml)

    def add_bullet(self, text, bold_prefix="", level=0):
        left_indent = 360 + (level * 360)
        hanging = 240
        b_xml = ""
        if bold_prefix:
            b_xml = f"""
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                    <w:b/>
                    <w:sz w:val="22"/>
                    <w:color w:val="0F172A"/>
                </w:rPr>
                <w:t xml:space="preserve">{self.escape(bold_prefix)} </w:t>
            </w:r>
            """
        
        xml = f"""
        <w:p>
            <w:pPr>
                <w:ind w:left="{left_indent}" w:hanging="{hanging}"/>
                <w:spacing w:line="260" w:lineRule="auto" w:after="80"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Symbol" w:hAnsi="Symbol"/>
                    <w:sz w:val="22"/>
                    <w:color w:val="1D4ED8"/>
                </w:rPr>
                <w:t>·   </w:t>
            </w:r>
            {b_xml}
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                    <w:sz w:val="22"/>
                    <w:color w:val="1E293B"/>
                </w:rPr>
                <w:t xml:space="preserve">{self.escape(text)}</w:t>
            </w:r>
        </w:p>
        """
        self.body_elements.append(xml)

    def add_callout(self, title, text, style="info"):
        bg_color = "F0F9FF" if style == "info" else "FFFBEB"
        border_color = "0284C7" if style == "info" else "D97706"
        
        xml = f"""
        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="9360" w:type="dxa"/>
                <w:tblCellMar>
                    <w:top w:w="140" w:type="dxa"/>
                    <w:left w:w="200" w:type="dxa"/>
                    <w:bottom w:w="140" w:type="dxa"/>
                    <w:right w:w="200" w:type="dxa"/>
                </w:tblCellMar>
                <w:tblBorders>
                    <w:top w:val="none"/>
                    <w:left w:val="single" w:sz="24" w:space="0" w:color="{border_color}"/>
                    <w:bottom w:val="none"/>
                    <w:right w:val="none"/>
                    <w:insideH w:val="none"/>
                    <w:insideV w:val="none"/>
                </w:tblBorders>
            </w:tblPr>
            <w:tr>
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="9360" w:type="dxa"/>
                        <w:shd w:val="clear" w:color="auto" w:fill="{bg_color}"/>
                    </w:tcPr>
                    <w:p>
                        <w:pPr><w:spacing w:after="60"/></w:pPr>
                        <w:r>
                            <w:rPr>
                                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                                <w:b/>
                                <w:sz w:val="22"/>
                                <w:color w:val="{border_color}"/>
                            </w:rPr>
                            <w:t xml:space="preserve">{self.escape(title)}</w:t>
                        </w:r>
                    </w:p>
                    <w:p>
                        <w:pPr><w:spacing w:line="260" w:lineRule="auto" w:after="0"/></w:pPr>
                        <w:r>
                            <w:rPr>
                                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                                <w:sz w:val="21"/>
                                <w:color w:val="1E293B"/>
                            </w:rPr>
                            <w:t xml:space="preserve">{self.escape(text)}</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            </w:tr>
        </w:tbl>
        <w:p><w:pPr><w:spacing w:before="0" w:after="120"/></w:pPr></w:p>
        """
        self.body_elements.append(xml)

    def add_table(self, headers, rows, col_widths=None):
        total_width = 9360
        num_cols = len(headers)
        if not col_widths:
            w = int(total_width / num_cols)
            col_widths = [w] * num_cols
            
        xml = f"""
        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="{total_width}" w:type="dxa"/>
                <w:tblCellMar>
                    <w:top w:w="100" w:type="dxa"/>
                    <w:left w:w="120" w:type="dxa"/>
                    <w:bottom w:w="100" w:type="dxa"/>
                    <w:right w:w="120" w:type="dxa"/>
                </w:tblCellMar>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:left w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:bottom w:val="single" w:sz="8" w:space="0" w:color="94A3B8"/>
                    <w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                    <w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                </w:tblBorders>
            </w:tblPr>
            <w:tr>
                <w:trPr><w:tblHeader/></w:trPr>
        """
        for i, h in enumerate(headers):
            cw = col_widths[i] if i < len(col_widths) else int(total_width / num_cols)
            xml += f"""
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="{cw}" w:type="dxa"/>
                        <w:shd w:val="clear" w:color="auto" w:fill="1E3A8A"/>
                        <w:vAlign w:val="center"/>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <w:jc w:val="center"/>
                            <w:spacing w:after="0"/>
                        </w:pPr>
                        <w:r>
                            <w:rPr>
                                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                                <w:b/>
                                <w:sz w:val="21"/>
                                <w:color w:val="FFFFFF"/>
                            </w:rPr>
                            <w:t xml:space="preserve">{self.escape(h)}</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            """
        xml += "</w:tr>"
        
        for r_idx, row in enumerate(rows):
            bg = "F8FAFC" if (r_idx % 2 == 1) else "FFFFFF"
            xml += "<w:tr>"
            for c_idx, cell in enumerate(row):
                cw = col_widths[c_idx] if c_idx < len(col_widths) else int(total_width / num_cols)
                xml += f"""
                    <w:tc>
                        <w:tcPr>
                            <w:tcW w:w="{cw}" w:type="dxa"/>
                            <w:shd w:val="clear" w:color="auto" w:fill="{bg}"/>
                            <w:vAlign w:val="center"/>
                        </w:tcPr>
                        <w:p>
                            <w:pPr>
                                <w:jc w:val="left"/>
                                <w:spacing w:line="240" w:lineRule="auto" w:after="0"/>
                            </w:pPr>
                            <w:r>
                                <w:rPr>
                                    <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                                    <w:sz w:val="20"/>
                                    <w:color w:val="1E293B"/>
                                </w:rPr>
                                <w:t xml:space="preserve">{self.escape(cell)}</w:t>
                            </w:r>
                        </w:p>
                    </w:tc>
                """
            xml += "</w:tr>"
            
        xml += """
        </w:tbl>
        <w:p><w:pPr><w:spacing w:before="0" w:after="160"/></w:pPr></w:p>
        """
        self.body_elements.append(xml)

    def save(self, filepath):
        content_types = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
</Types>"""

        root_rels = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>"""

        doc_rels = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>
</Relationships>"""

        settings = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:zoom w:percent="100"/>
</w:settings>"""

        styles = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
        <w:sz w:val="22"/>
        <w:color w:val="1E293B"/>
        <w:lang w:val="id-ID"/>
      </w:rPr>
    </w:rPrDefault>
    <w:pPrDefault>
      <w:pPr>
        <w:spacing w:line="276" w:lineRule="auto" w:after="140"/>
      </w:pPr>
    </w:pPrDefault>
  </w:docDefaults>
</w:styles>"""

        body_inner = "".join(self.body_elements)
        doc_xml = f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>
    {body_inner}
    <w:sectPr>
      <w:pgSz w:w="11906" w:h="16838"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
    </w:sectPr>
  </w:body>
</w:document>"""

        with zipfile.ZipFile(filepath, 'w', zipfile.ZIP_DEFLATED) as z:
            z.writestr("[Content_Types].xml", content_types)
            z.writestr("_rels/.rels", root_rels)
            z.writestr("word/_rels/document.xml.rels", doc_rels)
            z.writestr("word/document.xml", doc_xml)
            z.writestr("word/styles.xml", styles)
            z.writestr("word/settings.xml", settings)

        print(f"File berhasil disimpan di: {filepath} ({os.path.getsize(filepath)} bytes)")

print("DocxWriter class ready.")

