import { createContext, useContext, useState, useEffect } from "react";

const NavigationContext = createContext();

export const NavigationProvider = ({ children }) => {
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [openStructure, setOpenStructure] = useState(false);
  const [openRH, setOpenRH] = useState(false);

  const [theme, setTheme] = useState("light");

  // Charger depuis localStorage
  useEffect(() => {
    const saved = localStorage.getItem("theme") ?? "light";
    setTheme(saved);
    document.body.classList.toggle("dark-mode", saved === "dark");
  }, []);

  const toggleTheme = () => {
    const newTheme = theme === "light" ? "dark" : "light";
    setTheme(newTheme);
    localStorage.setItem("theme", newTheme);
    document.body.classList.toggle("dark-mode", newTheme === "dark");
  };

  return (
    <NavigationContext.Provider
      value={{
        collapsed,
        setCollapsed,
        mobileOpen,
        setMobileOpen,
        openStructure,
        setOpenStructure,
        openRH,
        setOpenRH,
        theme,
        toggleTheme,
      }}
    >
      {children}
    </NavigationContext.Provider>
  );
};

export const useNavigation = () => useContext(NavigationContext);
