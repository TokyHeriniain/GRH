import React from "react";
import { Table } from "react-bootstrap";

const n = (v) => Number(v ?? 0);

const ClotureTable = React.memo(({ rows, annee, showValidation = false }) => {
  const anneeCloture = annee;
  const anneeSuivante = annee + 1;

  const totals = rows.reduce(
    (acc, r) => {
      acc.soldeAvant += n(r.solde_actuel);
      acc.report += n(r.report);
      acc.perte += n(r.perte);
      acc.soldeApres += n(r.nouveau_solde);
      return acc;
    },
    { soldeAvant: 0, report: 0, perte: 0, soldeApres: 0 }
  );

  return (
    <Table bordered hover size="sm" className="align-middle">
      <thead className="table-light">
        <tr>
          <th>Personnels</th>
          <th className="text-end">Solde {anneeCloture}</th>
          <th className="text-end">Report</th>
          <th className="text-end text-danger">Perte</th>
          <th className="text-end">Solde {anneeSuivante}</th>
          {showValidation && (
            <>
              <th className="text-end">Validé le</th>
            </>
          )}
        </tr>
      </thead>

      <tbody>
        {rows.map((r, index) => {
          const key = [
            r.leave_balance_id,
            r.personnel_id,
            r.matricule,
            r.annee_reference ?? annee,
            index
          ].filter(Boolean).join("-");

          return (
            <tr
            key={key}
            className={Number(r.perte) > 0 ? "table-warning" : ""}
            >
              <td>
                {r.personnel}
                {Number(r.perte) > 0 && (
                    <span className="ms-2 badge bg-danger">Perte</span>
                )}
                {Number(r.perte) === 0 && Number(r.report) > 0 && (
                    <span className="ms-2 badge bg-success">OK</span>
                )}
              </td>

              <td className="text-end">{n(r.solde_actuel).toFixed(2)}</td>
              <td className="text-end">{n(r.report).toFixed(2)}</td>
              <td className={`text-end ${n(r.perte) > 0 ? "text-danger fw-bold" : ""}`}>
                {n(r.perte).toFixed(2)}
              </td>
              <td className="text-end fw-bold">
                {n(r.nouveau_solde).toFixed(2)}
              </td>

              {showValidation && (
                <>
                  <td className="text-end">{r.validated_at_local}</td>
                </>
              )}
            </tr>
          );
        })}
        <tr className="table-dark fw-bold">
          <td>TOTAL</td>
          <td className="text-end">{totals.soldeAvant.toFixed(2)}</td>
          <td className="text-end">{totals.report.toFixed(2)}</td>
          <td className="text-end text-danger">{totals.perte.toFixed(2)}</td>
          <td className="text-end">{totals.soldeApres.toFixed(2)}</td>
          {showValidation && <td colSpan={2} />}
        </tr>
      </tbody>
    </Table>
  );
});

export default ClotureTable;
