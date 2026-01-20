import React from "react";

export default function TauxApprobationCard({ taux }) {
  return (
    <div className="card text-center shadow-sm">
      <div className="card-header fw-bold">
        Taux d’approbation RH
      </div>

      <div className="card-body">
        <h1 className="display-4 text-success">
          {taux}%
        </h1>
        <p className="text-muted">
          Congés approuvés / demandes totales
        </p>
      </div>
    </div>
  );
}
