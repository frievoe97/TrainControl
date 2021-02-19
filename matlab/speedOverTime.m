fname = '../json/speedOverTime.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val = jsondecode(str);

speedOverTime_x = val(:,1)-1612811700;

speedOverTime_y = val(:,2);


plot(speedOverTime_x,speedOverTime_y);
