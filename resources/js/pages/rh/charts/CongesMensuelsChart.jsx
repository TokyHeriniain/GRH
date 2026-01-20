import React from "react";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

const MOIS = [
  "", "Jan", "Fév", "Mar", "Avr", "Mai", "Juin",
  "Juil", "Août", "Sep", "Oct", "Nov", "Déc"
];

export default function CongesMensuelsChart({ data }) {
  if (!data || data.length === 0) {
    return <p className="text-muted">Aucune donnée disponible</p>;
  }

  const formatted = data.map((d) => ({
    mois: MOIS[d.mois],
    total: d.total,
  }));

  return (
    <div className="card shadow-sm">
      <div className="card-header fw-bold">
        Congés par mois
      </div>

      <div className="card-body" style={{ height: 300 }}>
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={formatted}>
            <XAxis dataKey="mois" />
            <YAxis allowDecimals={false} />
            <Tooltip />
            <Bar dataKey="total" />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}
