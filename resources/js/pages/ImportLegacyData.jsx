import React, { useState, useEffect } from "react";
import axios from "../axios";
import { FaFileExcel, FaUpload, FaHistory, FaTrashAlt } from "react-icons/fa";
import { toast } from "react-toastify";

const ImportLegacyData = ({ onImportSuccess }) => {
  const [file, setFile] = useState(null);
  const [rapport, setRapport] = useState(null);
  const [progress, setProgress] = useState(0);
  const [loading, setLoading] = useState(false);
  const [historiques, setHistoriques] = useState([]);

  useEffect(() => {
    fetchHistoriques();
  }, []);

  const fetchHistoriques = async () => {
    try {
      const res = await axios.get("/api/import/history");
      setHistoriques(res.data);
    } catch (err) {
      console.error(err);
    }
  };

  const handleFileChange = (e) => {
    setFile(e.target.files[0]);
    setRapport(null);
  };

  const handleImport = async () => {
    if (!file) {
      toast.warn("Veuillez sélectionner un fichier Excel avant d'importer !");
      return;
    }

    const formData = new FormData();
    formData.append("file", file);

    setLoading(true);
    setProgress(0);

    try {
      const res = await axios.post("/api/import/legacy", formData, {
        headers: { "Content-Type": "multipart/form-data" },
        onUploadProgress: (progressEvent) => {
          const percentCompleted = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          );
          setProgress(percentCompleted);
        },
      });

      setRapport(res.data.rapport);
      toast.success("✅ Importation terminée avec succès !");
      if (onImportSuccess) onImportSuccess();
      fetchHistoriques();
    } catch (err) {
      console.error(err);
      toast.error("❌ Erreur lors de l'importation");
    } finally {
      setLoading(false);
      setTimeout(() => setProgress(0), 2000);
    }
  };

  const handleDeleteHistory = async (id) => {
    if (!window.confirm("Supprimer cet historique ?")) return;
    try {
      await axios.delete(`/api/import/history/${id}`);
      toast.success("🗑️ Historique supprimé");
      fetchHistoriques();
    } catch (err) {
      toast.error("Erreur suppression historique");
    }
  };

  return (
    <div className="p-4 bg-white rounded-2xl shadow-md border border-gray-200">
      <h2 className="text-lg font-semibold mb-3 flex items-center gap-2">
        <FaUpload className="text-blue-500" />
        Importer les personnels + soldes de congés
      </h2>

      {/* === Zone de sélection de fichier === */}
      <div className="flex items-center gap-3">
        <input
          type="file"
          accept=".xlsx,.xls,.csv"
          onChange={handleFileChange}
          className="border p-2 rounded w-full text-sm"
        />
        <button
          onClick={handleImport}
          disabled={loading}
          className={`px-4 py-2 rounded text-black font-medium ${
            loading ? "bg-gray-400" : "bg-green-600 hover:bg-green-700"
          }`}
        >
          {loading ? "Import en cours..." : "Importer"}
        </button>
      </div>

      {/* === Barre de progression === */}
      {progress > 0 && (
        <div className="w-full bg-gray-200 rounded-full h-2.5 mt-3">
          <div
            className="bg-green-600 h-2.5 rounded-full transition-all duration-500"
            style={{ width: `${progress}%` }}
          ></div>
        </div>
      )}

      {/* === Rapport d'import === */}
      {rapport && (
        <div className="mt-5">
          <h3 className="text-md font-semibold mb-2">📊 Résultat d'import :</h3>
          <p className="text-sm mb-2">
            ✅ <strong>{rapport.importes}</strong> personnels importés
          </p>

          {rapport.erreurs.length > 0 ? (
            <div className="mt-2 border-t pt-2">
              <p className="text-red-600 font-semibold">
                ⚠️ {rapport.erreurs.length} erreur(s)
              </p>
              <ul className="text-sm list-disc pl-5 text-gray-700 mt-1 max-h-40 overflow-y-auto">
                {rapport.erreurs.map((err, i) => (
                  <li key={i}>
                    Ligne {err.ligne} — {err.matricule} :{" "}
                    <span className="text-red-500">{err.erreur}</span>
                  </li>
                ))}
              </ul>
            </div>
          ) : (
            <p className="text-green-600 text-sm">Aucune erreur détectée 🎉</p>
          )}
        </div>
      )}

      {/* === Historique des imports === */}
      <div className="mt-6">
        <h3 className="text-md font-semibold mb-3 flex items-center gap-2">
          <FaHistory className="text-gray-500" /> Historique des imports
        </h3>

        {historiques.length === 0 ? (
          <p className="text-gray-500 text-sm">Aucun import enregistré.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm border border-gray-200 rounded-md">
              <thead className="bg-gray-100 text-gray-700">
                <tr>
                  <th className="p-2 text-left">Fichier</th>
                  <th className="p-2 text-left">Importés</th>
                  <th className="p-2 text-left">Erreurs</th>
                  <th className="p-2 text-left">Date</th>
                  <th className="p-2 text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                {Array.isArray(historiques) && historiques.map((h) => (
                  <tr key={h.id} className="border-t hover:bg-gray-50">
                    <td className="p-2">{h.fichier}</td>
                    <td className="p-2 text-green-700 font-medium">
                      {h.importes}
                    </td>
                    <td className="p-2 text-red-600 font-medium">
                      {h.erreurs?.length || 0}
                    </td>
                    <td className="p-2 text-gray-500">
                      {new Date(h.created_at).toLocaleString("fr-FR")}
                    </td>
                    <td className="p-2 text-center">
                      <button
                        onClick={() => handleDeleteHistory(h.id)}
                        className="text-red-600 hover:text-red-800"
                        title="Supprimer"
                      >
                        <FaTrashAlt />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <div className="mt-4">
        <a
          href="/modeles/personnels_et_soldes.xlsx"
          className="text-blue-600 hover:underline flex items-center gap-2 text-sm"
          download
        >
          <FaFileExcel /> Télécharger le modèle Excel d’import
        </a>
      </div>
    </div>
  );
};

export default ImportLegacyData;
