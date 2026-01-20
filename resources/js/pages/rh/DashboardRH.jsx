import React, { useEffect, useState } from "react";
import axios from "axios";

import CongesParTypeChart from "./charts/CongesParTypeChart";
import CongesMensuelsChart from "./charts/CongesMensuelsChart";
import TauxApprobationCard from "./charts/TauxApprobationCard";
import NavigationLayout from "../../components/NavigationLayout";

export default function DashboardRH() {
  const [data, setData] = useState(null);

  const fetchDashboard = async () => {
    const res = await axios.get("/api/rh/dashboard");
    setData(res.data);
  };

  useEffect(() => {
    fetchDashboard();
  }, []);

  if (!data) return <p>Chargement...</p>;

  return (
    <NavigationLayout>
      <div className="container mt-4">
      <div className="row g-3">
        <div className="col-md-4">
          <TauxApprobationCard taux={data.indicateurs.taux_approbation} />
        </div>

        <div className="col-md-8">
          <CongesParTypeChart data={data.repartition_par_type} />
        </div>

        <div className="col-md-12">
          <CongesMensuelsChart data={data.repartition_mensuelle} />
        </div>
      </div>
    </div>
    </NavigationLayout>
    
  );
}
