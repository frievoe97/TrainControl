fname = '../json/speedOverPosition.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val = jsondecode(str);

speedOverPosition_x = val(:,1);

speedOverPosition_y = val(:,2);


plot(speedOverPosition_x,speedOverPosition_y);
