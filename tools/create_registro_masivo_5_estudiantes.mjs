import fs from "node:fs/promises";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const outputDir = "C:/xampp/htdocs/sistema_acceso/outputs/registro_masivo_5_estudiantes";
const outputFile = `${outputDir}/registro_masivo_5_estudiantes.xlsx`;

await fs.mkdir(outputDir, { recursive: true });

const workbook = Workbook.create();
const sheet = workbook.worksheets.add("Registro masivo");
sheet.showGridLines = false;
sheet.tabColor = "#0D3B66";

const rows = [
  ["nombres", "apellidos", "dni", "edad", "sexo", "correo", "especialidad", "ciclo", "semestre", "turno"],
  ["Andrea Milagros", "Huamán Rojas", "88810001", 18, "femenino", "andrea.huaman.prueba@example.test", "CONTABILIDAD", "Ciclo I", "Semestre 1", "manana"],
  ["Diego Alonso", "Paredes Vela", "88810002", 19, "masculino", "diego.paredes.prueba@example.test", "CONTABILIDAD", "Ciclo I", "Semestre 1", "tarde"],
  ["Karla Sofía", "Mendoza Salazar", "88810003", 18, "femenino", "karla.mendoza.prueba@example.test", "CONTABILIDAD", "Ciclo I", "Semestre 1", "manana"],
  ["Luis Fernando", "Chávez Torres", "88810004", 20, "masculino", "luis.chavez.prueba@example.test", "CONTABILIDAD", "Ciclo I", "Semestre 1", "tarde"],
  ["Noelia Marisol", "Vargas Ríos", "88810005", 19, "femenino", "noelia.vargas.prueba@example.test", "CONTABILIDAD", "Ciclo I", "Semestre 1", "manana"],
];

sheet.getRange("A1:J6").values = rows;
sheet.getRange("A1:J1").format = {
  fill: "#0D3B66",
  font: { bold: true, color: "#FFFFFF" },
  horizontalAlignment: "center",
  verticalAlignment: "center",
  wrapText: true,
  borders: { preset: "all", style: "thin", color: "#164B78" },
};
sheet.getRange("A2:J6").format = {
  font: { color: "#172B3A" },
  verticalAlignment: "center",
  borders: { preset: "all", style: "thin", color: "#C8D6E5" },
};
sheet.getRange("A2:J2").format.fill = "#EAF3FA";
sheet.getRange("A4:J4").format.fill = "#EAF3FA";
sheet.getRange("A6:J6").format.fill = "#EAF3FA";
sheet.getRange("A1:J1").format.rowHeight = 28;
sheet.getRange("A2:J6").format.rowHeight = 22;

const widths = [20, 22, 14, 9, 13, 38, 21, 14, 16, 13];
for (let index = 0; index < widths.length; index += 1) {
  sheet.getRangeByIndexes(0, index, 6, 1).format.columnWidth = widths[index];
}

sheet.getRange("C2:C6").format.numberFormat = "@";
sheet.getRange("D2:D6").format.numberFormat = "0";
sheet.getRange("E2:E100").dataValidation = {
  rule: { type: "list", values: ["masculino", "femenino", "otro"] },
};
sheet.getRange("J2:J100").dataValidation = {
  rule: { type: "list", values: ["manana", "tarde"] },
};
sheet.freezePanes.freezeRows(1);
sheet.tables.add("A1:J6", true, "RegistroMasivo");

const check = await workbook.inspect({
  kind: "table,region",
  sheetId: "Registro masivo",
  range: "A1:J6",
  maxChars: 5000,
  tableMaxRows: 10,
  tableMaxCols: 10,
});
console.log(check.ndjson);

const formulaCheck = await workbook.inspect({
  kind: "formula",
  sheetId: "Registro masivo",
  range: "A1:J6",
  maxChars: 2000,
});
console.log(formulaCheck.ndjson);

const preview = await workbook.render({
  sheetName: "Registro masivo",
  range: "A1:J6",
  autoCrop: "all",
  scale: 1.5,
  format: "png",
});
await fs.writeFile(`${outputDir}/registro_masivo_5_estudiantes_preview.png`, new Uint8Array(await preview.arrayBuffer()));

const xlsx = await SpreadsheetFile.exportXlsx(workbook);
await xlsx.save(outputFile);
console.log(`Archivo creado: ${outputFile}`);
