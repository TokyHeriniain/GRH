import React, { useRef, useState } from "react";
import { Button, Spinner } from "react-bootstrap";
import { FaFileImport } from "react-icons/fa";
import api from "../axios";
import { toast } from "react-toastify";

/**
 * ImportLegacyData
 * -------------------------------------------------
 * Composant FACTORISÉ
 * - Bouton unique
 * - Input file caché
 * - Callbacks parent (progress / finish)
 * -------------------------------------------------
 */
export default function ImportLegacyData({
  disabled = false,
  onImportStart,
  onImportProgress,
  onImportFinish,
}) {
  const fileRef = useRef(null);
  const [loading, setLoading] = useState(false);

  const handleClick = () => {
    if (fileRef.current) {
      fileRef.current.value = null; // reset
      fileRef.current.click();
    }
  };

  const handleFileChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("file", file);

    try {
      setLoading(true);
      onImportStart?.();

      await api.post("/api/import/legacy", formData, {
        headers: { "Content-Type": "multipart/form-data" },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            const percent = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            );
            onImportProgress?.(Math.min(percent, 95));
          }
        },
      });

      toast.success("Import legacy RH terminé avec succès");
      onImportFinish?.();

    } catch (error) {
      console.error(error);
      toast.error(
        error.response?.data?.message ||
        "Erreur lors de l'import legacy RH"
      );
      onImportProgress?.(0);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <input        
        type="file"
        ref={fileRef}
        accept=".xlsx,.xls"
        style={{ display: "none" }}
        onChange={handleFileChange}
        disabled={disabled}
      />

      <Button
        variant="warning"
        onClick={handleClick}
        disabled={disabled || loading}
        title={
          disabled
            ? "Action réservée aux administrateurs RH"
            : "Importer les soldes historiques RH"
        }
      >
        {loading ? (
          <>
            <Spinner size="sm" animation="border" /> Import…
          </>
        ) : (
          <>
            <FaFileImport /> Import legacy RH
          </>
        )}
      </Button>
    </>
  );
}
