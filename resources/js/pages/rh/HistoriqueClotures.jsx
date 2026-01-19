import React, { useEffect, useState, useMemo } from "react";
import axios from "axios";
import { Table, Spinner, Alert } from "react-bootstrap";

export default function HistoriqueClotures() {
  const [annees, setAnnees] = useState([]);
  const [anneeSelected, setAnneeSelected] = useState(null);
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(false);

  /* ===============================
     FETCH HISTORIQUE DES ANNÉES
  =============================== */
  useEffect(() => {
    axios
      .get("/api/rh/cloture/historique")
      .then((res) => {
        const data = Array.isArray(res.data) ? res.data : [];
        console.log(data);
        setAnnees(data);
        if (data.length > 0) setAnneeSelected(data[0]);
      })
      .catch(() => setAnnees([]));
  }, []);

  /* ===============================
     FETCH DONNÉES DE L'ANNÉE SÉLECTIONNÉE
  =============================== */
  useEffect(() => {
    if (!anneeSelected) return;

    setLoading(true);
    axios
      .get(`/api/rh/cloture/closed/${anneeSelected}`)
      .then((res) => setRows(res.data || []))
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [anneeSelected]);

  /* ===============================
     TOTAUX
  =============================== */
  const totals = useMemo(() => {
    return rows.reduce(
      (acc, r) => {
        acc.solde += Number(r.solde_actuel || 0);
        acc.report += Number(r.report || 0);
        acc.perte += Number(r.perte || 0);
        acc.nouveau += Number(r.nouveau_solde || 0);
        return acc;
      },
      { solde: 0, report: 0, perte: 0, nouveau: 0 }
    );
  }, [rows]);

  return (
      <>
        <h4 className="mb-3">📜 Historique des clôtures RH</h4>

        {/* SELECT ANNÉE */}
        {annees.length > 0 ? (
          <div className="mb-3" style={{ maxWidth: 200 }}>
            <label className="form-label">Année</label>
            <select
              className="form-select"
              value={anneeSelected || ""}
              onChange={(e) => setAnneeSelected(Number(e.target.value))}
            >
              {annees.map((y) => (
                <option key={y} value={y}>
                  {y}
                </option>
              ))}
            </select>
          </div>
        ) : (
          <Alert variant="info">Aucune clôture enregistrée pour l'instant.</Alert>
        )}

        {/* LOADING */}
        {loading && (
          <div className="text-center my-4">
            <Spinner animation="border" />
          </div>
        )}

        {/* TABLE */}
        {!loading && rows.length > 0 && (
          <>
            <Table bordered hover size="sm" className="align-middle">
              <thead className="table-light">
                <tr>
                  <th>Personnel</th>
                  <th className="text-end">Solde</th>
                  <th className="text-end">Report</th>
                  <th className="text-end text-danger">Perte</th>
                  <th className="text-end">Solde N+1</th>
                  <th className="text-end">Validé par (ID)</th>
                  <th className="text-end">Date validation</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r, i) => (
                  <tr key={i}>
                    <td>{r.personnel}</td>
                    <td className="text-end">{r.solde_actuel}</td>
                    <td className="text-end">{r.report}</td>
                    <td className="text-end text-danger">{r.perte}</td>
                    <td className="text-end fw-bold">{r.nouveau_solde}</td>
                    <td className="text-end">{r.validated_by}</td>
                    <td className="text-end">{r.validated_at}</td>
                  </tr>
                ))}

                {/* TOTAL */}
                <tr className="table-secondary fw-bold">
                  <td>TOTAL</td>
                  <td className="text-end">{totals.solde.toFixed(2)}</td>
                  <td className="text-end">{totals.report.toFixed(2)}</td>
                  <td className="text-end text-danger">{totals.perte.toFixed(2)}</td>
                  <td className="text-end">{totals.nouveau.toFixed(2)}</td>
                  <td colSpan={2}></td>
                </tr>
              </tbody>
            </Table>
          </>
        )}

        {/* PAS DE DONNÉES */}
        {!loading && rows.length === 0 && annees.length > 0 && (
          <Alert variant="info">Aucune donnée pour l'année {anneeSelected}.</Alert>
        )}
      </>
  );
}
