// utils/excelToDate.js
import dayjs from 'dayjs'

export const excelToDate = (excelValue) => {
  if (!excelValue || isNaN(excelValue)) return null;

  try {
    // Excel commence à 1899-12-30
    return dayjs('1899-12-30').add(excelValue, 'day').format('YYYY-MM-DD');
  } catch (e) {
    return null;
  }
};
