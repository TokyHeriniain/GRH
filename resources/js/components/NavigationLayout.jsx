import Topbar from "./Topbar";
import Sidebar from "./Sidebar";
import { NavigationProvider } from "./NavigationContext";

const NavigationLayout = ({ children }) => {
  return (
    <NavigationProvider>
      <Topbar />
      <Sidebar />

      <div className="main-content" style={{ marginLeft: "260px", padding: "20px" }}>
        {children}
      </div>
    </NavigationProvider>
  );
};

export default NavigationLayout;
